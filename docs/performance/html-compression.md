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
