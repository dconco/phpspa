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

export const preloadStylesFromContent = async (content: string, scopeKey?: string): Promise<HTMLDivElement> => {
   const normalizedScopeKey = normalizeScopeKey(scopeKey)

   const nextHrefs: Set<string> = new Set()
   const toLoad: Map<string, string> = new Map()

   // --- Use regex to extract stylesheet links before DOM parsing triggers loads ---
   const linkRegex = /<link[^>]+rel=["']stylesheet["'][^>]*>/gi
   const hrefRegex = /href=["']([^"']+)["']/i
   
   const strippedContent = content.replace(linkRegex, (match) => {
      const hrefMatch = match.match(hrefRegex)
      if (hrefMatch && hrefMatch[1]) {
         const rawHref = hrefMatch[1]
         const resolvedHref = resolveHref(rawHref)
         nextHrefs.add(resolvedHref)
         
         if (!RuntimeManager.executedStyles.has(resolvedHref)) {
            toLoad.set(resolvedHref, rawHref)
         }
      }
      return '' // Remove the link from content
   })

   // --- Update tracking for this scope ---
   setScopeStyles(normalizedScopeKey, nextHrefs)

   // --- If no new styles to load, return immediately to maximize speed ---
   if (toLoad.size === 0) {
      const tempElem = document.createElement('div')
      tempElem.innerHTML = strippedContent
      return tempElem
   }

   const headLinks = getHeadStylesheetLinks()

   const loadPromises = Array.from(toLoad.entries()).map(([resolvedHref, rawHref]) => {
      // --- Double-check cache (race condition safety) ---
      if (RuntimeManager.executedStyles.has(resolvedHref)) {
         return Promise.resolve()
      }

      // --- Mark as loaded immediately to prevent future redundant loads ---
      RuntimeManager.executedStyles.add(resolvedHref)

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

      return waitForStylesheet(headLink)
   })

   await Promise.all(loadPromises)
   await waitForNextPaint()

   const tempElem = document.createElement('div')
   tempElem.innerHTML = strippedContent
   return tempElem
}
