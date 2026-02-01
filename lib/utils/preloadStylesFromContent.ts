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

export const preloadStylesFromContent = async (content: string): Promise<HTMLDivElement> => {
   const tempElem = document.createElement('div')
   tempElem.innerHTML = content

   const links = Array.from(tempElem.querySelectorAll<HTMLLinkElement>('link[rel="stylesheet"]'))

   if (links.length === 0) {
      return tempElem
   }

   const headLinks = Array.from(document.head.querySelectorAll<HTMLLinkElement>('link[rel="stylesheet"]'))

   const loadPromises = links.map((link) => {
      const href = link.getAttribute('href')
      link.remove()

      if (!href) {
         return Promise.resolve()
      }

      let resolvedHref = ''
      try {
         resolvedHref = new URL(href, location.toString()).href
      } catch {
         resolvedHref = href
      }

      let headLink = headLinks.find((existing) => {
         try {
            return existing.href === resolvedHref
         } catch {
            return existing.getAttribute('href') === href
         }
      })

      if (!headLink) {
         headLink = link.cloneNode(true) as HTMLLinkElement
         document.head.appendChild(headLink)
         headLinks.push(headLink)
      }

      return waitForStylesheet(headLink)
   })

   await Promise.all(loadPromises)
   await waitForNextPaint()

   return tempElem
}
