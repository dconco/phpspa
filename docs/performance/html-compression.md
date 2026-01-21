# Performance: HTML Compression

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

To ensure your application is as fast as possible, PhpSPA includes a powerful, built-in HTML compressor. It automatically minifies your final HTML output by removing whitespace, comments, and other unnecessary characters, which reduces the page size and leads to faster load times. ⚡

!!! info "Auto-Detection"
    By default, PhpSPA tries to auto-detect the best settings. However, you can take full control for fine-tuned performance.

## Automatic Compression (Default Behavior)

PhpSPA automatically initializes the HTML compressor when you create your `App` instance. The second parameter controls this behavior and is set to `true` by default.

```php
<?php
use PhpSPA\App;

// Auto-initialization is enabled by default
$app = new App($layout);

// This is the same as:
$app = new App($layout, true);
```

!!! success "Smart Detection"
    When auto-initialization is enabled, PhpSPA automatically detects the best compression level based on your server environment. You don't need to manually configure anything!

### Disabling Auto-Initialization

If you want full manual control over compression, you can disable auto-initialization:

```php
<?php
use PhpSPA\App;

// Disable auto-initialization
$app = new App($layout, false);

// Now you must manually configure compression if you want it
$app->compression(Compressor::LEVEL_AGGRESSIVE, true);
```

!!! tip "When to Disable"
    Disable auto-initialization only if you need precise control over compression settings for specific use cases.

## Environment-Based Configuration (Recommended)

The easiest way to manage compression is to set the application's environment. PhpSPA will then apply a sensible preset for you.

=== "development"

    Compression is disabled to make debugging easier.

    ```php
    <?php
    use PhpSPA\Compression\Compressor;

    $app->compressionEnvironment(Compressor::ENV_DEVELOPMENT);
    ```

=== "production"

    A high level of compression is enabled for maximum performance.

    ```php
    <?php
    use PhpSPA\Compression\Compressor;

    $app->compressionEnvironment(Compressor::ENV_PRODUCTION);
    ```

!!! tip "Environment Presets"
    Set the environment to automatically configure compression with sensible defaults.

## Manual Compression Control

For more granular control, you can manually set the compression level and enable or disable Gzip.

```php
<?php
use PhpSPA\App;
use PhpSPA\Compression\Compressor;

$app = new App($layout);

// Manually set the highest level of compression and enable Gzip
$app->compression(Compressor::LEVEL_EXTREME, true);
```

!!! success "Compression Levels"
    There are several levels available, from basic to extreme:
    
    - **`Compressor::LEVEL_NONE`**: No compression is applied
    - **`Compressor::LEVEL_BASIC`**: Removes comments and basic whitespace
    - **`Compressor::LEVEL_AGGRESSIVE`**: Performs more intense whitespace removal
    - **`Compressor::LEVEL_EXTREME`**: Applies the most aggressive minification for the smallest possible file size

## Asset Caching Behavior

!!! success "Smart Caching with Auto-Invalidation"
    When compression is enabled (any level above `LEVEL_NONE`), PhpSPA caches compressed assets to `.generated.php` files for faster subsequent requests. The cache automatically invalidates when source files change!

### How Asset Caching Works

When you enable compression, PhpSPA generates cached files alongside your component files:

```
examples/components/
├── Counter.php
└── generated/
    ├── Counter-0.js.generated.php       # Cached compressed JS
    ├── Counter-0.js.generated.php.map   # Cache validation map
    └── Counter-0.css.generated.php      # Cached compressed CSS
    └── Counter-0.css.generated.php.map  # Cache validation map
```

**Smart Cache Invalidation:** PhpSPA automatically detects when source files change by tracking file sizes in `.map` files. When a change is detected:

1. The cached `.generated.php` file is regenerated
2. The `.map` file is updated with the new file size
3. Fresh compressed content is served

This means:

- ✅ **Production**: Excellent performance - compressed content is reused when unchanged
- ✅ **Development**: Always fresh - changes are automatically detected and cache is rebuilt

### Recommended Environment Setup

Use environment-based configuration to automatically handle caching:

```php
<?php
use PhpSPA\App;

// Read from environment variable
$app = new App($layout);
$app->compressionEnvironment($_ENV['APP_ENV']);
```

Then configure your `.env` file:

=== "Development (.env)"

    ```bash
    APP_ENV=development
    ```
    
    - Compression disabled (`LEVEL_NONE`)
    - No cached files generated
    - Changes appear immediately (no compression overhead)
    - Existing cached files automatically deleted when switching to development

=== "Production (.env)"

    ```bash
    APP_ENV=production
    ```
    
    - Maximum compression (`LEVEL_EXTREME`)
    - Assets cached for performance
    - Blazing fast subsequent requests
    - Generated files reused until source changes detected

!!! tip "Environment Switching"
    When you switch from production back to development mode (`LEVEL_NONE`), PhpSPA automatically **deletes** cached `.generated.php` files to prevent stale content issues.

### Manual Cache Management

The cache is automatically managed, but you can manually clear it if needed:

```bash
# Delete all generated cache files and maps
find . -name "*.generated.php*" -type f -delete
```

Or temporarily disable compression to bypass the cache entirely:

```php
<?php
// Force fresh assets without caching
$app->compression(Compressor::LEVEL_NONE);
```

!!! tip "Automatic Cache Validation"
    PhpSPA tracks file sizes in `.map` files and automatically regenerates cached assets when source files change. You typically don't need to manually clear the cache!

### IIFE Wrapping for Component Scripts

PhpSPA automatically wraps component JavaScript in Immediately Invoked Function Expressions (IIFE) for scope isolation:

- **Component JS**: Always wrapped in IIFE `(()=>{/* your code */})()`
- **Global JS**: Only wrapped when requested via PHPSPA (SPA navigation) to execute in isolation
- **External script links**: Never wrapped (assumed pre-bundled)

This ensures:
- Component scripts don't pollute the global scope
- Variables and functions are properly isolated
- Global scripts can re-execute during SPA navigation
