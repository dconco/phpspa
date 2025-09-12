# phpSPA v1.1.7

Enhanced global asset management system for better performance and control

---

## What's New in v1.1.7

### 🚀 Major Features Added

-  **🎨 Global Asset Management**: Comprehensive system for managing application-wide scripts and stylesheets
-  **⏰ Asset Cache Control**: Fine-tuned control over asset caching duration
-  **🔗 Session-based Asset Links**: Enhanced asset delivery with session-based link generation
-  **🌐 Global Script & Style Support**: Add scripts and styles that apply to every component render

### 🌟 Key Benefits

- **Better Performance**: Optimized asset delivery with configurable caching
- **Global Control**: Apply scripts and styles across all components from the App level
- **Flexible Caching**: Control asset cache duration from session-only to custom hours
- **Method Chaining**: Fluent API design for easy configuration

---

## New Methods Overview

### `assetCacheHours(int $hours)`
Configure how long assets are cached by the browser and session system.

```php
$app->assetCacheHours(48); // Cache for 48 hours
$app->assetCacheHours(0);  // Session-only caching
```

### `script(callable $script)`
Add global JavaScript that executes on every component render.

```php
$app->script(function() {
    return "console.log('App initialized');";
});
```

### `styleSheet(callable $style)`
Add global CSS that applies to every component render.

```php
$app->styleSheet(function() {
    return "body { margin: 0; padding: 0; }";
});
```

---

## Example Usage

```php
use phpSPA\App;

$app = new App('layout')
    ->assetCacheHours(24)
    ->script(function() {
        return "
            // Global analytics tracking
            gtag('config', 'GA_MEASUREMENT_ID');
            
            // Global error handling
            window.onerror = function(msg, url, line) {
                console.error('Global error:', msg, 'at', url + ':' + line);
            };
        ";
    })
    ->styleSheet(function() {
        return "
            /* Global reset styles */
            * {
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            
            /* Global utility classes */
            .text-center { text-align: center; }
            .hidden { display: none !important; }
        ";
    });

$app->component('/', function() {
    return "<h1>Welcome to phpSPA v1.1.7!</h1>";
});

$app->render();
```

---

## Documentation Sections

1. [🎨 Global Asset Management](1-global-asset-management.md) - Complete guide to the new asset system
2. [⏰ Asset Caching Control](2-asset-caching-control.md) - Understanding and configuring asset caching
3. [📋 Migration Guide](3-migration-guide.md) - Upgrading from v1.1.6 to v1.1.7

---

## Compatibility

!!! note "Backward Compatibility"
    phpSPA v1.1.7 is fully backward compatible with v1.1.6. All existing applications will continue to work without modifications.

!!! tip "JavaScript Engine"
    This version works with [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) version `v1.1.7` and above.