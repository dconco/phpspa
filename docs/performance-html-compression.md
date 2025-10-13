## Performance: HTML Compression

To ensure your application is as fast as possible, PhpSPA includes a powerful, built-in HTML compressor. It automatically minifies your final HTML output by removing whitespace, comments, and other unnecessary characters, which reduces the page size and leads to faster load times. ⚡

By default, PhpSPA tries to auto-detect the best settings. However, you can take full control for fine-tuned performance.

-----

### Environment-Based Configuration (Recommended)

The easiest way to manage compression is to set the application's environment. PhpSPA will then apply a sensible preset for you.

  * `development`: Compression is disabled to make debugging easier.
  * `production`: A high level of compression is enabled for maximum performance.

<!-- end list -->

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = new App($layout);

// Set the environment to automatically configure compression
$app->compressionEnvironment(Compressor::ENV_PRODUCTION);
```

-----

### Manual Compression Control

For more granular control, you can manually set the compression level and enable or disable Gzip.

There are several levels available, from basic to extreme.

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = new App($layout);

// Manually set the highest level of compression and enable Gzip
$app->compression(Compressor::LEVEL_EXTREME, true);
```

#### Available Levels:

  * **`Compressor::LEVEL_NONE`**: No compression is applied.
  * **`Compressor::LEVEL_BASIC`**: Removes comments and basic whitespace.
  * **`Compressor::LEVEL_AGGRESSIVE`**: Performs more intense whitespace removal.
  * **`Compressor::LEVEL_EXTREME`**: Applies the most aggressive minification for the smallest possible file size.
