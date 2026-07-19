import { RuntimeManager } from '../core/RuntimeManager'

const waitForNextPaint = (): Promise<void> =>
   new Promise((resolve) => {
      requestAnimationFrame(() => requestAnimationFrame(() => resolve()))
   })

const waitForStylesheet = (link: HTMLLinkElement): Promise<void> =>
   new Promise((resolve) => {
      if (link.sheet) {
         waitForNextPaint().then(resolve)
         return
      }

      const cleanup = () => {
         link.removeEventListener('load', onLoad)
         link.removeEventListener('error', onLoad)
         resolve()
      }

      const onLoad = () => cleanup()

      link.addEventListener('load', onLoad, { once: true })
      link.addEventListener('error', onLoad, { once: true })

   })

const DEFAULT_SCOPE_KEY = '__phpspa_default__'
const STYLESHEET_LINK_REGEX = /<link[^>]+rel=["']stylesheet["'][^>]*>/gi

const scopeToHrefs: Map<string, Set<string>> = new Map()
const hrefToScopes: Map<string, Set<string>> = new Map()
const ownedLinksByHref: Map<string, HTMLLinkElement> = new Map()

const normalizeScopeKey = (scopeKey?: string): string => {
   const key = (scopeKey ?? '').trim()
   return key.length > 0 ? key : DEFAULT_SCOPE_KEY
}

const resolveHref = (href: string): string => {
   try {
      // Use origin + pathname as base to stabilize resolution regardless of current query/hash
      const base = location.origin + location.pathname
      return new URL(href, base).href
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

const releaseScopeHref = (scopeKey: string, resolvedHref: string) => {
   const scopes = hrefToScopes.get(resolvedHref)
   if (scopes) {
      scopes.delete(scopeKey)
      if (scopes.size === 0) {
         hrefToScopes.delete(resolvedHref)

         const owned = ownedLinksByHref.get(resolvedHref)
         if (owned && owned.isConnected) {
            owned.remove()
         }
         ownedLinksByHref.delete(resolvedHref)
         RuntimeManager.executedStyles.delete(resolvedHref)
      }
   }
}

const setScopeStyles = (scopeKey: string, nextHrefs: Set<string>) => {
   const previous = scopeToHrefs.get(scopeKey) ?? new Set<string>()

   // remove old hrefs that are no longer needed by this scope
   for (const href of previous) {
      if (!nextHrefs.has(href)) {
         releaseScopeHref(scopeKey, href)
      }
   }

   // add new hrefs for this scope
   for (const href of nextHrefs) {
      if (!previous.has(href)) {
         let scopes = hrefToScopes.get(href)
         if (!scopes) {
            scopes = new Set<string>()
            hrefToScopes.set(href, scopes)
         }
         scopes.add(scopeKey)
      }
   }

   scopeToHrefs.set(scopeKey, new Set(nextHrefs))
}

export const clearPreloadedStylesForScope = (scopeKey: string) => {
   const key = normalizeScopeKey(scopeKey)
   setScopeStyles(key, new Set())
}

export const retainStylesheetLinks = (sourceContent: string, updatedContent: string): string => {
   const stylesheetLinks = sourceContent.match(STYLESHEET_LINK_REGEX)
   return stylesheetLinks ? stylesheetLinks.join('') + updatedContent : updatedContent
}

export const preloadStylesFromContent =  (content: string, scopeKey?: string): { element: HTMLDivElement; ready: Promise<void> } => {
   const normalizedScopeKey = normalizeScopeKey(scopeKey)

   const nextHrefs: Set<string> = new Set()
   const rawHrefs: Map<string, string> = new Map()

   // --- Use regex to extract stylesheet links before DOM parsing triggers loads ---
   const hrefRegex = /href=["']([^"']+)["']/i
   
   const strippedContent = content.replace(STYLESHEET_LINK_REGEX, (match) => {
      const hrefMatch = match.match(hrefRegex)
      if (hrefMatch && hrefMatch[1]) {
         const rawHref = hrefMatch[1]
         const resolvedHref = resolveHref(rawHref)
         nextHrefs.add(resolvedHref)
         rawHrefs.set(resolvedHref, rawHref)
      }
      return '' // Remove the link from content
   })

   const tempElem = document.createElement('div')
   tempElem.innerHTML = strippedContent

   // --- Execute any inline styles in the new content ---
   RuntimeManager.runStylesForElement(tempElem)

   // --- Keep current styles until the caller is ready to replace the DOM ---
   if (nextHrefs.size === 0) {
      const ready = Promise.resolve()
         .then(() => setScopeStyles(normalizedScopeKey, nextHrefs))

      return { element: tempElem, ready }
   }

   const headLinks = getHeadStylesheetLinks()

   const loadPromises = Array.from(rawHrefs.entries()).map(([resolvedHref, rawHref]) => {
      // prefer a previously-owned managed link if still present
      const owned = ownedLinksByHref.get(resolvedHref)
      let headLink: HTMLLinkElement | null = (owned && owned.isConnected) ? owned : null

      if (!headLink) {
         headLink = findExistingHeadLink(resolvedHref, rawHref, headLinks)
      }

      if (!headLink) {
         headLink = document.createElement('link')
         headLink.rel = 'stylesheet'
         headLink.href = rawHref
         headLink.setAttribute('data-phpspa-managed', '1')
         headLink.setAttribute('data-phpspa-scope', normalizedScopeKey)
         document.head.appendChild(headLink)
         headLinks.push(headLink)
         ownedLinksByHref.set(resolvedHref, headLink)
      }

      RuntimeManager.executedStyles.add(resolvedHref)
      return waitForStylesheet(headLink)
   })

   const ready = Promise.all(loadPromises)
      .then(() => setScopeStyles(normalizedScopeKey, nextHrefs))

   return { element: tempElem, ready }
}
