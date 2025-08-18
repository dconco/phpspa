# HTML Compression Usage Guide

## Overview

phpSPA now includes built-in HTML compression to reduce payload sizes and improve performance. The compression system automatically minifies HTML and can apply gzip compression.

## Basic Usage

### Auto-Configuration (Recommended)

```php
<?php
// Compression is enabled automatically based on environment
$app = new App('layout');
```

### Manual Configuration

```php
<?php
// Custom compression settings
$app = new App('layout')
    ->compression(2, true); // Level 2 (aggressive), gzip enabled

// Or set by environment
$app = new App('layout')
    ->compressionEnvironment('production');
```

### Disable Compression

```php
<?php
// Disable auto-initialization, then set no compression
$app = new App('layout', false)
    ->compression(0, false); // No compression
```

## Compression Levels

| Level | Name       | Description                                      |
| ----- | ---------- | ------------------------------------------------ |
| 0     | None       | No compression applied                           |
| 1     | Basic      | Remove comments, extra whitespace                |
| 2     | Aggressive | Additional minification, remove empty attributes |
| 3     | Extreme    | Maximum compression, may affect readability      |

## Environment Auto-Detection

The system automatically detects your environment:

- **Development**: No compression (level 0)
  - localhost, 127.0.0.1, *.local, *.dev domains
  - CLI server mode
  - APP_ENV=development

- **Staging**: Basic compression (level 1)
  - staging subdomains
  - APP_ENV=staging

- **Production**: Aggressive compression (level 2)
  - All other environments
  - APP_ENV=production

## Manual Control

```php
<?php
use phpSPA\Core\Config\CompressionConfig;
use phpSPA\Core\Utils\HtmlCompressor;

// Initialize for specific environment
CompressionConfig::initialize('production');

// Custom settings
CompressionConfig::custom(3, true); // Extreme compression + gzip

// Direct control
HtmlCompressor::setLevel(HtmlCompressor::LEVEL_AGGRESSIVE);
HtmlCompressor::setGzipEnabled(true);
```

## Performance Benefits

### File Size Reduction
- **Basic**: 15-25% size reduction
- **Aggressive**: 25-40% size reduction  
- **Extreme**: 40-60% size reduction
- **With Gzip**: Additional 60-80% reduction

### Example Results
```
Original HTML: 25KB
Basic compression: 18KB (-28%)
Aggressive + gzip: 4KB (-84%)
```

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

- **Gzip compression**: Supported by all modern browsers
- **HTML minification**: Compatible with all browsers
- **Graceful fallback**: Works without compression if needed
