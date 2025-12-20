import type { EventPayload, PhpSPAInstance } from '@dconco/phpspa'
import { toggleNavLinks } from './toggleNavLinks'

export function registerDebugHooks(instance: PhpSPAInstance | null | undefined): void {
   if (!instance || typeof instance.on !== 'function') {
      console.warn('PhpSPA instance missing on().')
      return
   }

   instance.on('load', (event: EventPayload) => {
      toggleNavLinks()
      console.log('[Helper] Loaded:', event.route)
   })

   instance.on('beforeload', (event: EventPayload) => {
      console.log('[Helper] Attempting to load:', event.route)
   })
}
