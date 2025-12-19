export function registerDebugHooks(instance) {
  if (!instance || typeof instance.on !== 'function') {
    console.warn('PhpSPA instance missing on().');
    return;
  }

  instance.on('load', (event) => {
    console.log('[Helper] Loaded:', event.route);
  });

  instance.on('beforeload', (event) => {
    console.log('[Helper] Before load:', event.route);
  });
}
