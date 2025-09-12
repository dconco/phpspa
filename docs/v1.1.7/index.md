# phpSPA v1.1.7

Enhanced asset management and global script/style injection for modern web applications

---

## What's New in v1.1.7

### 🔧 Asset Management Enhancements

- **🕒 Configurable Asset Caching**: Control CSS/JS asset cache duration with `App::assetCacheHours()`
- **🌐 Global Script Injection**: Add application-wide JavaScript with `App::script()`
- **🎨 Global Stylesheet Injection**: Include application-wide CSS with `App::styleSheet()`

### 🐛 Bug Fixes & Improvements

- **HTML Compression**: Fixed critical space removal issues between element names and attributes
- **JavaScript Compression**: Enhanced minification with better handling of modern JavaScript APIs
- **UTF-8 Support**: Improved handling of special characters and emojis in compression
- **Test Suite**: Enhanced reliability and fixed function redeclare errors

---

## Update To `v1.1.7`

```bash
composer update dconco/phpspa:v1.1.7
```

---

## Core Features

### 1. Asset Cache Management

Control how long CSS and JavaScript assets are cached:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->assetCacheHours(12) // Cache for 12 hours
    ->attach(require 'components/App.php')
    ->run();

// Session-only caching (no persistence)
$app->assetCacheHours(0);
```

**Benefits:**
- Improved performance with optimized caching
- Flexible cache duration control
- Session-only option for development

### 2. Global Script Injection

Add JavaScript that runs on every component render:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->script(fn() => <<<JS
        // Global analytics tracking
        console.log("Page rendered at: " + new Date());
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error caught:', e.error);
        });
    JS)
    ->attach(require 'components/App.php')
    ->run();
```

### 3. Global Stylesheet Injection

Add CSS that applies to every component:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->styleSheet(fn() => <<<CSS
        /* Global typography */
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
        }
        
        /* Global utility classes */
        .text-center { text-align: center; }
        .hidden { display: none; }
    CSS)
    ->attach(require 'components/App.php')
    ->run();
```

### 4. Method Chaining

Combine all features with fluent syntax:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->assetCacheHours(24)
    ->script(fn() => 'console.log("App initialized");')
    ->styleSheet(fn() => 'body { margin: 0; }')
    ->attach(require 'components/Home.php')
    ->attach(require 'components/About.php')
    ->defaultTargetID('app')
    ->run();
```

---

## Documentation

- [Asset Cache Management](./asset-cache-management.md) - Configure asset caching behavior
- [Global Scripts and Styles](./global-scripts-and-styles.md) - Inject application-wide assets

---

## Migration from v1.1.5

v1.1.7 is fully backward compatible with v1.1.5. The new features are additive:

```php
<?php
// Your existing v1.1.5 code works unchanged
$app = new App(require 'Layout.php');
$app->attach(require 'components/App.php');
$app->run();

// Enhance with v1.1.7 features
$app = (new App(require 'Layout.php'))
    ->assetCacheHours(12) // NEW: Configure caching
    ->script(fn() => 'console.log("Enhanced!");') // NEW: Global script
    ->styleSheet(fn() => 'body { font-size: 16px; }') // NEW: Global style
    ->attach(require 'components/App.php')
    ->run();
```

---

## Performance Impact

The new features provide performance benefits:

- **Asset Caching**: Reduces server load and improves response times
- **Global Assets**: Eliminates duplication across components
- **Optimized Injection**: Efficient asset management with minimal overhead

---

*Ready to get started? Check out the [Asset Cache Management](./asset-cache-management.md) guide!*