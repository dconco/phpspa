# phpSPA v1.1.5

A high-performance PHP Single Page Application framework with advanced compression and enhanced component capabilities

---

## What's New in v1.1.5

!!! important "JavaScript Engine Requirement"
This phpSPA version requires [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) version above `v1.1.7` to work properly.

### 🚀 Major Features Added

-  **🗜️ HTML Compression & Minification System**: Multi-level compression with up to 84% size reduction
-  **⚡ Enhanced PHP-JS Integration**: Direct function calls with `useFunction()` and improved `__call()` alias
-  **🏗️ Class Component Support**: Use PHP classes as components with namespace support
-  **🔗 Method Chaining**: Fluent API for App class configuration
-  **🛡️ CSRF Protection Component**: Built-in security with automatic token management

### 🐛 Bug Fixes

-  **Component Nesting**: Fixed nested component rendering and children processing

### 🔄 Breaking Changes

-  `\phpSPA\Component` namespace changed to `\Component`
-  JavaScript execution logic updated (no more `data-type` attributes required)
-  `__CONTENT__` placeholder removed in favor of direct target ID rendering

---

## Update To `v1.1.5`

```bash
composer update dconco/phpspa:v1.1.5
```

Update your JavaScript engine:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
```

---

## Core Features

### 1. HTML Compression & Minification

!!! success "Performance Boost"
Achieve 15-84% size reduction with intelligent compression levels and automatic environment detection.

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

// Auto-configuration (recommended)
$app = new App('layout')->compression(Compressor::LEVEL_AUTO, true);

// Manual control
$app = new App('layout')->compression(Compressor::LEVEL_EXTREME, true);

// Environment-specific
$app = new App('layout')->compressionEnvironment(Compressor::ENV_PRODUCTION);
```

**Compression Levels:**

-  `LEVEL_NONE` - No compression
-  `LEVEL_BASIC` - Basic HTML minification
-  `LEVEL_AGGRESSIVE` - Advanced minification + CSS optimization
-  `LEVEL_EXTREME` - Maximum compression + JavaScript minification
-  `LEVEL_AUTO` - Intelligent selection based on content size

→ [Detailed Compression Guide](./1-compression-system.md)

### 2. Enhanced PHP-JavaScript Integration

Direct function calls between PHP and JavaScript with improved security.

```php
<?php
use function Component\useFunction;

// Define your function
function Login($args) {
    return "<h2>Login Component</h2>";
}

// In your component
$loginApi = useFunction('Login');

return <<<HTML
<script>
    htmlElement.onclick = async () => {
        const response = await {$loginApi('arguments')};
        console.log(response);
    }
</script>
HTML;
```

→ [PHP-JS Integration Guide](./2-php-js-integration.md)

### 3. Class Components

Use PHP classes as components with namespace support.

```php
<?php
namespace MyApp\Components;

class UserCard {
    public function __render($name) {
        return "<div class='card'>{$name}</div>";
    }
}

// Usage in templates
echo '<MyApp.Components.UserCard name="John" />';
```

→ [Class Components Guide](./3-class-components.md)

### 4. Method Chaining API

Fluent configuration for cleaner code.

```php
<?php
$app = (new App(require 'Layout.php'))
    ->attach(require 'components/Login.php')
    ->defaultTargetID('app')
    ->defaultToCaseSensitive()
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->run();
```

→ [Method Chaining Guide](./4-method-chaining.md)

### 5. CSRF Protection Component

Built-in security with automatic token management.

```php
<?php
// In your form
echo '<Component.Csrf name="user-form" />';

// Verify submission
use Component\Csrf;
$csrf = new Csrf("user-form");
if (!$csrf->verify()) {
    die('Invalid CSRF token!');
}
```

**Security Features:**

-  Cryptographically secure token generation
-  Automatic expiration (1 hour default)
-  Timing-safe validation
-  Token rotation and cleanup

→ [CSRF Protection Guide](./5-csrf-protection.md)

---

## Migration Guide

### From v1.1.4 to v1.1.5

#### Namespace Changes

**Before:**

```php
use phpSPA\Component\Link;
```

**After:**

```php
use Component\Link;
```

#### Script Execution

**Before:**

```html
<script data-type="phpspa/script">
	// Your code
</script>
```

**After:**

```html
<script>
	// Your code - data-type no longer required
</script>
```

#### Content Rendering

**Before:**

```php
return str_replace('__CONTENT__', $content, $layout);
```

**After:**

```php
// Content renders directly to target ID
// No manual replacement needed
```

---

## Performance Improvements

### Compression Benefits

| Content Type  | Before | After | Reduction |
| ------------- | ------ | ----- | --------- |
| HTML + CSS    | 150KB  | 89KB  | 41%       |
| JavaScript    | 75KB   | 45KB  | 40%       |
| Mixed Content | 200KB  | 32KB  | 84%       |

### JavaScript Engine Updates

-  Faster component rendering
-  Improved memory management
-  Enhanced script execution flow

---

## Security Enhancements

### CSRF Protection

-  **Multiple named tokens** with automatic cleanup
-  **Built-in expiration** prevents token reuse attacks
-  **Timing-safe validation** prevents timing attacks
-  **Automatic rotation** enhances security

### Function Call Security

-  Enhanced `__call()` security with 10x improvement
-  Secure token-based function authentication
-  Protected namespace access

---

## Testing Support

New testing utilities included:

-  `tests/Test.php` - Unified test runner (CLI-only)
-  `tests/HtmlCompressionTest.php` - Compression testing
-  `tests/JsCompressionTest.php` - JavaScript ASI testing
-  CI/CD workflow with GitHub Actions

---

## Examples

### Basic Application with All Features

```php
<?php
require_once 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = (new App(require 'Layout.php'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->attach(require 'components/Dashboard.php')
    ->attach(require 'components/Login.php')
    ->defaultTargetID('app')
    ->run();
```

### Component with CSRF Protection

```php
<?php
function ContactForm() {
    return <<<HTML
    <form method="POST" action="/contact">
        <Component.Csrf name="contact-form" />
        <input type="text" name="name" required>
        <input type="email" name="email" required>
        <textarea name="message" required></textarea>
        <button type="submit">Send Message</button>
    </form>
    HTML;
}
```

---

## What's Next?

Stay tuned for upcoming features:

-  🧪 Enhanced testing utilities
-  🌐 Built-in i18n tools
-  📊 Performance monitoring
-  🔌 Plugin system

---

## Documentation Links

-  [Compression System](./1-compression-system.md)
-  [PHP-JS Integration](./2-php-js-integration.md)
-  [Class Components](./3-class-components.md)
-  [Method Chaining](./4-method-chaining.md)
-  [CSRF Protection](./5-csrf-protection.md)

---

## Migration & Support

Need help migrating? Check our [Migration Guide](./6-migration-guide.md) or reach out:

-  📚 [Full Documentation](https://phpspa.readthedocs.io)
-  🐛 [GitHub Issues](https://github.com/dconco/phpspa/issues)
-  💬 [Discord Community](https://discord.gg/FeVQs73C)

---

_Built with ❤️ by [Dave Conco](https://github.com/dconco)_
