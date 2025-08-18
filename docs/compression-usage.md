# HTML Compression Usage Guide

## Overview

phpSPA now includes built-in HTML compression to reduce payload sizes and improve performance. The compression system automatically minifies HTML and can apply gzip compression.

## Basic Usage

Import the necessary classes

```php
<?php

use phpSPA\App;
use phpSPA\Compression\Compressor;
```

### Check Gzip Support

Before using compression, check if your server supports gzip compression:

```php
<?php
// Check if gzip compression is available
if (Compressor::supportsGzip()) {
    echo "Gzip compression is supported";
} else {
    echo "Gzip compression is not available";
    // Enable gzip in your php.ini: 
    // zlib.output_compression = On
    // or install/enable the zlib extension
}
```

### Auto-Configuration (Recommended)

```php
<?php
// Compression is enabled automatically based on environment
$app = new App('layout');

// Compression is enabled automatically based on HTML output size
$app = new App('layout')->compression(Compressor::LEVEL_AUTO);
```

### Manual Configuration

```php
<?php
// Custom compression settings
$app = new App('layout')
    ->compression(Compressor::AGGRESSIVE, true); // Level 3 (aggressive), gzip enabled

// Or set by environment
$app = new App('layout')
    ->compressionEnvironment(Compressor::ENV_PRODUCTION);
```

### Disable Compression

```php
<?php
// Disable auto-initialization, then set no compression
$app = new App('layout', false)
    ->compression(Compressor::LEVEL_NONE, false); // No compression
```

## Compression Levels

| Level | Name       | Description                                                            |
| ----- | ---------- | ---------------------------------------------------------------------- |
| 0     | None       | No compression applied                                                 |
| 1     | Auto       | Automatic compression based on HTML output size                        |
| 2     | Basic      | Remove comments, extra whitespace                                      |
| 3     | Aggressive | Basic + JS/CSS minification, remove empty attributes                   |
| 4     | Extreme    | Aggressive + maximum JS/CSS compression, remove all unnecessary spaces |

## Environment Auto-Detection

The system automatically detects your environment:

- **Development**: No compression (level 0)
  - localhost, 127.0.0.1, *.local, *.dev domains
  - CLI server mode
  - APP_ENV=development

- **Staging**: Basic compression (level 2)
  - staging subdomains
  - APP_ENV=staging

- **Production**: Aggressive compression (level 3)
  - All other environments
  - APP_ENV=production

## Auto-Detection by HTML Output Size

When using `Compressor::LEVEL_AUTO`, the system automatically selects the optimal compression level based on your HTML content size:

```php
<?php
// Auto-detection based on content size
$app = new App('layout')->compression(Compressor::LEVEL_AUTO);
```

**Size-Based Compression Levels:**

- **< 1KB**: Basic compression (level 2)
  - Small content doesn't benefit much from aggressive compression
  - Faster processing for small pages

- **1KB - 10KB**: Aggressive compression (level 3)
  - Medium-sized content benefits from JS/CSS minification
  - Good balance between compression and processing time

- **> 10KB**: Extreme compression (level 4)
  - Large content gets maximum compression benefits
  - Worth the extra processing time for significant size reduction

This auto-detection ensures optimal performance for different content sizes without manual configuration.

## Manual Control

```php
<?php
use phpSPA\Core\Config\CompressionConfig;
use phpSPA\Compression\Compressor;

// Initialize for specific environment
CompressionConfig::initialize(Compressor::ENV_PRODUCTION);

// Custom settings
CompressionConfig::custom(Compressor::LEVEL_EXTREME, true); // Extreme compression + gzip

// Direct control
Compressor::setLevel(HtmlCompressor::LEVEL_AGGRESSIVE);
Compressor::setGzipEnabled(true);
```

## Performance Benefits

### File Size Reduction

- **Basic**: 15-25% size reduction (HTML only)
- **Aggressive**: 25-40% size reduction (HTML + JS/CSS minification)
- **Extreme**: 40-60% size reduction (maximum compression)
- **With Gzip**: Additional 60-80% reduction on top of minification

### Real Example Results

From actual testing with JavaScript-heavy content:

```text
Original HTML with JS: 184 bytes
Basic compression: ~140 bytes (-24%)
Aggressive compression: 148 bytes (-20% + JS/CSS minification)
Extreme compression: 135 bytes (-27% + maximum JS/CSS compression)
```

**Additional space savings in Extreme level:**

- Removes spaces around operators: `' + route'` → `'+route'`
- Removes spaces around parentheses: `if (route)` → `if(route)`
- Removes spaces around braces: `{ document` → `{document`
- Maximum whitespace elimination while preserving functionality

## Best Practices

1. **Use auto-detection** for most projects
2. **Test thoroughly** with higher compression levels
3. **Monitor performance** in production
4. **Consider caching** for optimal results

## Debugging

```php
<?php
use phpSPA\Core\Config\CompressionConfig;

// Get current settings
$info = CompressionConfig::getInfo();
print_r($info);

/*
Output:
Array (
    [environment] => production
    [compression_enabled] => true
    [gzip_supported] => true
    [client_accepts_gzip] => true
)
*/
```

## Browser Compatibility

-  **Gzip compression**: Supported by all modern browsers
-  **HTML minification**: Compatible with all browsers
-  **Graceful fallback**: Works without compression if needed
