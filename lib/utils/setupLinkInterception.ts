import { AppManager } from "../core/AppManager"

/**
 * Intercepts clicks on Component.Link generated anchors and routes through AppManager
 */
export function setupLinkInterception() {
   document.addEventListener("click", (event) => {
      if (event.defaultPrevented || event.button !== 0) return
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return

      const target = (event.target as HTMLElement)?.closest?.('a[data-type="phpspa-link-tag"]')
      if (!target) return
      if (target.hasAttribute('download')) return
      if (target.getAttribute('target') && target.getAttribute('target') !== '_self') return

      const href = target.getAttribute('href')
      if (!href) return

      const url = new URL(href, location.toString())
      if (url.origin !== location.origin) return // --- external links fallback to default behaviour ---

      event.preventDefault()
      AppManager.navigate(url, 'push')
   })
}
