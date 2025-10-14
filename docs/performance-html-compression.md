# Performance: HTML Compression

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

To ensure your application is as fast as possible, PhpSPA includes a powerful, built-in HTML compressor. It automatically minifies your final HTML output by removing whitespace, comments, and other unnecessary characters, which reduces the page size and leads to faster load times. ⚡

!!! info "Auto-Detection"
    By default, PhpSPA tries to auto-detect the best settings. However, you can take full control for fine-tuned performance.

## Environment-Based Configuration (Recommended)

The easiest way to manage compression is to set the application's environment. PhpSPA will then apply a sensible preset for you.

=== "development"

    Compression is disabled to make debugging easier.

    ```php
    $app->compressionEnvironment(Compressor::ENV_DEVELOPMENT);
    ```

=== "production"

    A high level of compression is enabled for maximum performance.

    ```php
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
