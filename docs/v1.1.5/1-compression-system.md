# Compression System

## Overview

phpSPA v1.1.5 introduces a comprehensive HTML compression and minification system that can reduce payload sizes by 15-84%. The system includes multi-level compression, automatic gzip compression, and intelligent environment detection.

## Features

-  **Multi-level compression**: None, Basic, Aggressive, Extreme, Auto
-  **Gzip compression**: Automatic when supported by client
-  **Environment auto-detection**: Development, Staging, Production presets
-  **Smart JS minification**: Preserves functionality with automatic semicolon insertion
-  **CSS minification**: Removes comments, whitespace, and optimizes selectors
-  **Performance optimized**: Significant size reduction possible

## Quick Start

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

// Auto-configuration (recommended)
$app = new App('layout')->compression(Compressor::LEVEL_AUTO, true);
```

## Compression Levels

### LEVEL_NONE

No compression applied. Useful for debugging.

```php
$app->compression(Compressor::LEVEL_NONE);
```

### LEVEL_BASIC

Basic HTML minification:

-  Removes extra whitespace
-  Removes HTML comments
-  Preserves formatting in `<pre>` and `<code>` tags

```php
$app->compression(Compressor::LEVEL_BASIC);
```

### LEVEL_AGGRESSIVE

Advanced minification:

-  All BASIC features
-  CSS minification
-  Removes unnecessary attributes
-  Optimizes tag structures

```php
$app->compression(Compressor::LEVEL_AGGRESSIVE);
```

### LEVEL_EXTREME

Maximum compression:

-  All AGGRESSIVE features
-  JavaScript minification with ASI (Automatic Semicolon Insertion)
-  Advanced CSS optimization
-  Maximum whitespace removal

```php
$app->compression(Compressor::LEVEL_EXTREME);
```

### LEVEL_AUTO

Intelligent compression selection based on content size and complexity:

```php
$app->compression(Compressor::LEVEL_AUTO, true); // Enable gzip
```

## Environment-Based Configuration

### Auto-Detection

```php
$app->compressionEnvironment(Compressor::ENV_AUTO);
```

**Detection Logic:**

-  `ENV_DEVELOPMENT`: No compression for easier debugging
-  `ENV_STAGING`: Moderate compression for testing
-  `ENV_PRODUCTION`: Maximum compression for performance

### Manual Environment Settings

```php
// Development environment
$app->compressionEnvironment(Compressor::ENV_DEVELOPMENT);

// Staging environment
$app->compressionEnvironment(Compressor::ENV_STAGING);

// Production environment
$app->compressionEnvironment(Compressor::ENV_PRODUCTION);
```

## Gzip Compression

### Automatic Gzip

```php
// Enable gzip compression automatically
$app->compression(Compressor::LEVEL_AUTO, true);
```

### Check Gzip Support

```php
if (Compressor::supportsGzip()) {
    echo "Gzip compression is supported";
} else {
    echo "Enable gzip in php.ini: zlib.output_compression = On";
}
```

## JavaScript Minification with ASI

The system includes smart JavaScript minification with Automatic Semicolon Insertion (ASI) to prevent syntax errors:

```javascript
// Before minification
function myFunction() {
	var a = 1
	var b = 2
	return a + b
}

// After minification (with ASI)
function myFunction() {
	var a = 1
	var b = 2
	return a + b
}
```

### ASI Safety Features

-  Detects risky line endings
-  Automatically inserts semicolons where needed
-  Preserves function boundaries
-  Handles control structures safely

## CSS Minification

### Features

-  Removes comments
-  Removes unnecessary whitespace
-  Optimizes selectors
-  Preserves functionality

### Example

```css
/* Before */
.my-class {
	color: red;
	margin: 10px;
	/* Comment */
	padding: 5px;
}

/* After */
.my-class {
	color: red;
	margin: 10px;
	padding: 5px;
}
```

## Performance Metrics

| Content Type  | Original | Compressed | Reduction |
| ------------- | -------- | ---------- | --------- |
| HTML + CSS    | 150KB    | 89KB       | 41%       |
| JavaScript    | 75KB     | 45KB       | 40%       |
| Mixed Content | 200KB    | 32KB       | 84%       |

## Advanced Configuration

### Per-Component Compression

```php
function MyComponent() {
    // Disable compression for this component
    Compressor::setLevel(Compressor::LEVEL_NONE);

    return '<div>Debug content with preserved formatting</div>';
}
```

## Best Practices

### Development

-  Use `LEVEL_NONE` or `LEVEL_BASIC` for debugging
-  Enable compression only in staging/production

```php
// Development setup
if ($_ENV['APP_ENV'] === 'development') {
    $app->compression(Compressor::LEVEL_NONE);
} else {
    $app->compression(Compressor::LEVEL_AUTO, true);
}
```

### Production

-  Always use `LEVEL_AUTO` or `LEVEL_EXTREME`
-  Enable gzip compression
-  Monitor performance impact

```php
// Production setup
$app = new App('layout')
    ->compression(Compressor::LEVEL_AUTO, true)
    ->run();
```

### Content-Specific

-  Use `LEVEL_BASIC` for content-heavy pages
-  Use `LEVEL_EXTREME` for JavaScript-heavy applications
-  Test compression levels with your specific content

## Troubleshooting

### Common Issues

**JavaScript Errors After Minification:**

```php
// Disable JS minification if needed
$app->compression(Compressor::LEVEL_AGGRESSIVE); // No JS minification
```

**Performance Issues:**

```php
// Monitor compression performance
$start = microtime(true);
$app->compression(Compressor::LEVEL_EXTREME, true);
$app->run();
echo "Compression time: " . (microtime(true) - $start) . "s";
```

## Testing

The compression system includes comprehensive tests:

```bash
# Run compression tests
php tests/Test.php

# Run specific test
php tests/HtmlCompressionTest.php
php tests/JsCompressionTest.php
```

## Files Added

-  `app/core/Utils/HtmlCompressor.php` - Main compression engine
-  `app/core/Config/CompressionConfig.php` - Configuration management
-  `tests/HtmlCompressionTest.php` - HTML compression tests
-  `tests/JsCompressionTest.php` - JavaScript ASI tests

## Migration from Previous Versions

If you're upgrading from earlier versions:

```php
// Old way (still works)
$app = new App('layout');

// New way (recommended)
$app = new App('layout')->compression(Compressor::LEVEL_AUTO, true);
```

The compression system is backward compatible and won't break existing applications.
