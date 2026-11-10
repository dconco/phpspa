import { RuntimeManager } from '../core/RuntimeManager'

const DEFAULT_SCOPE_KEY = '__phpspa_default__'

// --- Attribute selector, so unquoted markup from the HTML compressor still matches ---
const STYLESHEET_SELECTOR = 'link[rel~="stylesheet" i]:not([rel~="alternate" i])'

// --- A sheet with a non-matching media query still downloads and parses, but styles nothing ---
const INERT_MEDIA = 'not all'
const ORIGINAL_MEDIA_ATTRIBUTE = 'data-phpspa-media'

// --- A stalled stylesheet must not block navigation forever ---
const STYLESHEET_LOAD_TIMEOUT_MS = 5000

type StyleNode = HTMLLinkElement | HTMLStyleElement

const scopeToKeys: Map<string, Set<string>> = new Map()
const keyToScopes: Map<string, Set<string>> = new Map()
const ownedNodes: Map<string, StyleNode> = new Map()

const waitForStylesheet = (link: HTMLLinkElement): Promise<void> =>
   new Promise((resolve) => {
      if (link.sheet) {
         resolve()
         return
      }

      const finish = () => {
         clearTimeout(timer)
         link.removeEventListener('load', finish)
         link.removeEventListener('error', finish)
         resolve()
      }

      const timer = setTimeout(finish, STYLESHEET_LOAD_TIMEOUT_MS)

      link.addEventListener('load', finish)
      link.addEventListener('error', finish)
   })

const normalizeScopeKey = (scopeKey?: string): string => {
   const key = (scopeKey ?? '').trim()
   return key.length > 0 ? key : DEFAULT_SCOPE_KEY
}

const resolveHref = (href: string, baseUrl: string | URL): string => {
   try {
      return new URL(href, baseUrl).href
   } catch {
      return href
   }
}

const getHeadStylesheetLinks = (): HTMLLinkElement[] =>
   Array.from(document.head.querySelectorAll<HTMLLinkElement>('link[rel="stylesheet"]'))

const findExistingHeadLink = (resolvedHref: string, rawHref: string, headLinks: HTMLLinkElement[]): HTMLLinkElement | null => {
   for (const existing of headLinks) {
      try {
         if (existing.href === resolvedHref) return existing
      } catch {
         // ignore
      }

      if (existing.getAttribute('href') === rawHref) return existing
   }

   return null
}

const activateLink = (link: HTMLLinkElement) => {
   const media = link.getAttribute(ORIGINAL_MEDIA_ATTRIBUTE)

   if (media) link.media = media
   else if (link.media === INERT_MEDIA) link.removeAttribute('media')
}

const createInertLink = (parsed: HTMLLinkElement, resolvedHref: string, scopeKey: string): HTMLLinkElement => {
   const link = document.createElement('link')

   for (const attribute of Array.from(parsed.attributes)) {
      link.setAttribute(attribute.name, attribute.value)
   }

   const originalMedia = parsed.getAttribute('media')
   if (originalMedia) link.setAttribute(ORIGINAL_MEDIA_ATTRIBUTE, originalMedia)

   link.media = INERT_MEDIA
   link.href = resolvedHref
   link.setAttribute('data-phpspa-managed', '1')
   link.setAttribute('data-phpspa-scope', scopeKey)

   // --- Forget failed sheets so the next navigation retries instead of waiting on a dead link ---
   link.addEventListener('error', () => {
      link.remove()
      if (ownedNodes.get(resolvedHref) === link) ownedNodes.delete(resolvedHref)
   }, { once: true })

   document.head.appendChild(link)
   return link
}

const releaseScopeKey = (scopeKey: string, key: string) => {
   const scopes = keyToScopes.get(key)
   if (!scopes) return

   scopes.delete(scopeKey)
   if (scopes.size > 0) return

   keyToScopes.delete(key)
   ownedNodes.get(key)?.remove()
   ownedNodes.delete(key)
   RuntimeManager.executedStyles.delete(key)
}

const setScopeStyles = (scopeKey: string, nextKeys: Set<string>) => {
   const previous = scopeToKeys.get(scopeKey) ?? new Set<string>()

   for (const key of previous) {
      if (!nextKeys.has(key)) releaseScopeKey(scopeKey, key)
   }

   for (const key of nextKeys) {
      let scopes = keyToScopes.get(key)
      if (!scopes) {
         scopes = new Set<string>()
         keyToScopes.set(key, scopes)
      }
      scopes.add(scopeKey)
   }

   scopeToKeys.set(scopeKey, new Set(nextKeys))
}

// --- Drops sheets downloaded for a navigation that was superseded before it could commit ---
const removeAbandonedLinks = () => {
   ownedNodes.forEach((node, key) => {
      if (keyToScopes.has(key) || !(node instanceof HTMLLinkElement) || !node.sheet) return

      node.remove()
      ownedNodes.delete(key)
      RuntimeManager.executedStyles.delete(key)
   })
}

export const clearPreloadedStylesForScope = (scopeKey: string) => {
   setScopeStyles(normalizeScopeKey(scopeKey), new Set())
}

/**
 * Keeps the source content's style tags on a live-HTML snapshot, because they are
 * moved to <head> on navigation and so are missing from the target's current markup.
 */
export const retainStyleTags = (sourceContent: string, updatedContent: string): string => {
   const template = document.createElement('template')
   template.innerHTML = sourceContent

   const styleTags = Array.from(template.content.querySelectorAll(`${STYLESHEET_SELECTOR}, style`))

   return styleTags.map(node => node.outerHTML).join('') + updatedContent
}

/**
 * Prepares a component's styles without letting them affect the current page.
 *
 * Stylesheets download inert. `commitStyles` then switches styles over in one
 * synchronous step, so calling it in the same task as the DOM swap leaves the
 * browser no opportunity to paint a half-styled page.
 *
 * @param content - The component HTML
 * @param scopeKey - The target that owns these styles
 * @param baseUrl - The URL the content was rendered for, which relative hrefs are resolved against
 */
export const preloadStylesFromContent = (
   content: string,
   scopeKey?: string,
   baseUrl: string | URL = location.href
): { element: HTMLDivElement; ready: Promise<void[]>; commitStyles: () => void } => {
   const normalizedScopeKey = normalizeScopeKey(scopeKey)

   // --- Links and styles do nothing while detached, so parsing here cannot affect the page ---
   const element = document.createElement('div')
   element.innerHTML = content

   const nextKeys: Set<string> = new Set()
   const inlineStyles: Map<string, HTMLStyleElement> = new Map()
   const loadPromises: Promise<void>[] = []
   const headLinks = getHeadStylesheetLinks()

   element.querySelectorAll<HTMLLinkElement>(STYLESHEET_SELECTOR).forEach((parsed) => {
      parsed.remove()

      const rawHref = parsed.getAttribute('href')
      if (!rawHref) return

      const resolvedHref = resolveHref(rawHref, baseUrl)
      if (nextKeys.has(resolvedHref)) return

      nextKeys.add(resolvedHref)
      RuntimeManager.executedStyles.add(resolvedHref)

      let link = ownedNodes.get(resolvedHref) as HTMLLinkElement | undefined

      if (!link?.isConnected) {
         link = findExistingHeadLink(resolvedHref, rawHref, headLinks) ?? undefined
      }

      if (!link) {
         link = createInertLink(parsed, resolvedHref, normalizedScopeKey)
         ownedNodes.set(resolvedHref, link)
      }

      loadPromises.push(waitForStylesheet(link))
   })

   element.querySelectorAll('style').forEach((style) => {
      style.remove()

      const key = `inline:${style.textContent ?? ''}`
      nextKeys.add(key)
      inlineStyles.set(key, style)
   })

   const commitStyles = () => {
      inlineStyles.forEach((style, key) => {
         if (ownedNodes.has(key)) return

         document.head.appendChild(style)
         ownedNodes.set(key, style)
      })

      setScopeStyles(normalizedScopeKey, nextKeys)

      nextKeys.forEach((key) => {
         const node = ownedNodes.get(key)
         if (node instanceof HTMLLinkElement) activateLink(node)
      })

      removeAbandonedLinks()
   }

   return { element, ready: Promise.all(loadPromises), commitStyles }
}
