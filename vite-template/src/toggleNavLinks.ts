
import { useCallback } from '../../lib'

export const toggleNavLinks = () => {
   const navToggle = document.querySelector('[data-nav-toggle]') as HTMLElement | null
   const navLinks = document.querySelector('[data-nav-links]') as HTMLElement | null

   if (!navToggle || !navLinks) {
      return
   }

   const toggle = useCallback(() =>
      document.startViewTransition(() => navLinks.classList.toggle('hidden')),
   [navLinks])

   navToggle.addEventListener('click', toggle)
}
