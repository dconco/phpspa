import type { EventPayload, PhpSPAInstance } from '../../src/script/phpspa.mjs';

export function registerDebugHooks(instance: PhpSPAInstance | null | undefined): void {
   if (!instance || typeof instance.on !== 'function') {
      console.warn('PhpSPA instance missing on().');
      return;
   }

   instance.on('load', (event: EventPayload) => {
      console.log('[Helper] Loaded:', event.route);
   });

   instance.on('beforeload', (event: EventPayload) => {
      console.log('[Helper] Before load:', event.route);
   });
}
