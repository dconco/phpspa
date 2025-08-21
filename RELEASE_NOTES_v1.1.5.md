# 🚀 phpSPA v1.1.5: Performance & Security Powerhouse

_Released: August 20, 2025_

## 📋 Release Overview

phpSPA v1.1.5 delivers **massive performance improvements** with up to **84% size reduction** through intelligent compression, plus enhanced security features and modern developer experience improvements. This release introduces powerful new capabilities while maintaining backward compatibility.

---

## ⚠️ **Important Requirements**

> **JavaScript Engine Update Required**  
> This version requires [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) **v1.1.7+** to work properly.

```bash
# Update your dependencies
composer update dconco/phpspa:^1.1.5
```

```html
<!-- Update JavaScript engine -->
<script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
```

---

## ✨ **Major Features**

### 🗜️ **HTML Compression & Minification System**

Revolutionary compression system that delivers **15-84% size reduction** with intelligent optimization.

**Key Features:**

-  **Multi-level compression**: None, Basic, Aggressive, Extreme, Auto
-  **Automatic Gzip compression** when client supports it
-  **Smart JavaScript minification** with ASI (Automatic Semicolon Insertion)
-  **CSS optimization** with comment removal and whitespace reduction
-  **Environment auto-detection** for Development, Staging, Production

**Quick Start:**

```php
use phpSPA\Compression\Compressor;

// Auto-configuration (recommended)
$app = new App('layout')->compression(Compressor::LEVEL_AUTO, true);

// Environment-specific
$app->compressionEnvironment(Compressor::ENV_PRODUCTION);
```

**Performance Results:**

| Content Type  | Before | After | Reduction |
| ------------- | ------ | ----- | --------- |
| HTML + CSS    | 150KB  | 89KB  | **41%**   |
| JavaScript    | 75KB   | 45KB  | **40%**   |
| Mixed Content | 200KB  | 32KB  | **84%**   |

📚 [**Complete Documentation**](https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/)

---

### ⚡ **Enhanced PHP-JavaScript Integration**

Direct, secure function calls between PHP and JavaScript with 10x improved security.

**New `useFunction()` Utility:**

```php
use function Component\useFunction;

function Dashboard() {
   $userApi = useFunction('getUserData');

   return <<<HTML
   <script>
      document.addEventListener('DOMContentLoaded', async () => {
         const userData = await {$userApi(123)};
         console.log('User:', userData);
      });
   </script>
   HTML;
}
```

**Enhanced Features:**

-  **Token-based security** with automatic validation
-  **Namespace support** for organized function calls
-  **Async/await compatibility** for modern JavaScript
-  **Error handling** with try/catch support

📚 [**Integration Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/2-php-js-integration/)

---

### 🏗️ **Class Component Support**

Object-oriented component development with namespace support.

```php
// Define class component
namespace Components\UI;

class UserCard {
   public function __render($name = "Unknown") {
      return "<div class='user-card'>
         <h3>{$name}</h3>
      </div>";
   }
}

// Use with namespace syntax
echo '<Components.UI.UserCard name="John Doe" />';
```

**Features:**

-  **`__render` method** requirement for standardized interface
-  **Full namespace support** (`<Namespace.Class />`)
-  **Props handling** with validation capabilities
-  **Backward compatibility** with function components

📚 [**Class Components Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/3-class-components/)

---

### 🔗 **Method Chaining API**

Fluent configuration for cleaner, more expressive code.

**Before:**

```php
$app = new App(require 'Layout.php');
$app->attach(require 'components/Login.php');
$app->defaultTargetID('app');
$app->compression(Compressor::LEVEL_AUTO, true);
$app->cors();
$app->run();
```

**After:**

```php
$app = (new App(require 'Layout.php'))
   ->attach(require 'components/Login.php')
   ->defaultTargetID('app')
   ->compression(Compressor::LEVEL_AUTO, true)
   ->cors()
   ->run();
```

📚 [**Method Chaining Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/4-method-chaining/)

---

### 🛡️ **CSRF Protection Component**

Built-in security with automatic token management and timing-safe validation.

```php
// In your form
<form method="POST" action="/contact">
   <Component.Csrf name="contact-form" />
   <input type="text" name="name" required>
   <button type="submit">Submit</button>
</form>

// Verify submission
use Component\Csrf;
$csrf = new Csrf("contact-form");
if (!$csrf->verify()) {
   die('Invalid CSRF token!');
}
```

**Security Features:**

-  **Cryptographically secure** token generation
-  **Automatic expiration** (1 hour default)
-  **Timing-safe validation** prevents timing attacks
-  **Multiple named tokens** with automatic cleanup
-  **Token rotation** for enhanced security

📚 [**CSRF Protection Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/5-csrf-protection/)

---

## 🐛 **Bug Fixes**

### Component Rendering

-  **Fixed nested component rendering** - Components now properly process their children before being passed to parent components
-  **Improved data flow** - Changed from reference-based to return-value based processing for more reliable component resolution

---

## 🔄 **Breaking Changes**

### 1. Namespace Updates

```php
// OLD (v1.1.4)
use phpSPA\Component\Link;
use phpSPA\Component\useFunction;

// NEW (v1.1.5)
use Component\Link;
use function Component\useFunction;
```

### 2. Script Execution

```html
<!-- OLD: data-type attributes required -->
<script data-type="phpspa/script">
	// Your code
</script>

<!-- NEW: data-type no longer needed -->
<script>
	// Your code
</script>
```

### 3. Content Rendering

```php
// OLD: Manual content replacement
return str_replace('__CONTENT__', $content, $layout);

// NEW: Direct target ID rendering
// No manual replacement needed
```

---

## 📈 **Performance Improvements**

### Compression Benefits

-  **Up to 84% size reduction** with intelligent compression
-  **Automatic environment detection** for optimal settings
-  **Gzip compression** when supported by client
-  **Smart JavaScript minification** preserves functionality

### JavaScript Engine Updates

-  **Faster component rendering** with optimized execution flow
-  **Improved memory management** for better performance
-  **Enhanced script execution** without data-type requirements

---

## 🔒 **Security Enhancements**

### CSRF Protection

-  **Multiple named tokens** with automatic cleanup
-  **Built-in expiration** prevents token reuse attacks
-  **Timing-safe validation** prevents timing attacks
-  **Automatic rotation** enhances security

### Function Call Security

-  **10x more secure** `__call()` implementation
-  **Token-based authentication** for function access
-  **Protected namespace access** prevents unauthorized calls

---

## 🧪 **Testing & Quality**

### New Test Suite

-  **`tests/Test.php`** - Unified CLI-only test runner
-  **`tests/HtmlCompressionTest.php`** - Compression effectiveness tests
-  **`tests/JsCompressionTest.php`** - JavaScript ASI safety tests
-  **CI/CD integration** with GitHub Actions workflow

### Quality Assurance

-  **Comprehensive documentation** with `@see` annotations
-  **Code examples** for all new features
-  **Migration guides** for smooth upgrades

---

## 🚀 **Getting Started**

### Quick Setup

```php
<?php
require_once 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = (new App(require 'Layout.php'))
   ->compression(Compressor::LEVEL_AUTO, true)
   ->attach(require 'components/Dashboard.php')
   ->defaultTargetID('app')
   ->run();
```

### With CSRF Protection

```php
function ContactForm() {
   return <<<HTML
   <form method="POST" action="/contact">
      <Component.Csrf name="contact-form" />
      <input type="text" name="name" required>
      <button type="submit">Send</button>
   </form>
   HTML;
}
```

---

## 📚 **Documentation**

### Complete v1.1.5 Guides

-  🎉 [**Overview & Getting Started**](https://phpspa.readthedocs.io/en/latest/v1.1.5/)
-  🗜️ [**Compression System**](https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/)
-  ⚡ [**PHP-JS Integration**](https://phpspa.readthedocs.io/en/latest/v1.1.5/2-php-js-integration/)
-  🏗️ [**Class Components**](https://phpspa.readthedocs.io/en/latest/v1.1.5/3-class-components/)
-  🔗 [**Method Chaining**](https://phpspa.readthedocs.io/en/latest/v1.1.5/4-method-chaining/)
-  🛡️ [**CSRF Protection**](https://phpspa.readthedocs.io/en/latest/v1.1.5/5-csrf-protection/)
-  📋 [**Migration Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/6-migration-guide/)

---

## 🔄 **Migration**

### Step-by-Step Migration

1. **Update dependencies**: `composer update dconco/phpspa:^1.1.5`
2. **Update JS engine**: Use `phpspa-js@latest`
3. **Fix namespaces**: `phpSPA\Component` → `Component`
4. **Remove data-type**: From `<script>` and `<style>` tags
5. **Update layouts**: Remove `__CONTENT__` placeholders
6. **Test application**: Verify functionality

### Automated Migration

```bash
# Find and replace namespaces
find . -name "*.php" -exec sed -i 's/use phpSPA\\Component\\/use Component\\/g' {} +

# Remove data-type attributes
find . -name "*.php" -exec sed -i 's/<script data-type="phpspa\/script">/<script>/g' {} +
```

📚 [**Complete Migration Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/6-migration-guide/)

---

## 🎯 **What's Next**

### Upcoming Features

-  🧪 **Enhanced testing utilities** for component testing
-  🌐 **Built-in i18n tools** for internationalization
-  📊 **Performance monitoring** dashboard
-  🔌 **Plugin system** for extensibility

---

## 📞 **Support & Community**

### Get Help

-  📚 [**Documentation**](https://phpspa.readthedocs.io)
-  🐛 [**GitHub Issues**](https://github.com/dconco/phpspa/issues)
-  💬 [**Discord Community**](https://discord.gg/FeVQs73C)
-  🎬 [**YouTube Tutorials**](https://youtube.com/@daveconco)
-  🐦 [**Twitter Updates**](https://x.com/dave_conco)

### Contributing

We welcome contributions! Check our [Contributing Guide](https://github.com/dconco/phpspa/blob/main/CONTRIBUTING.md) to get started.

---

## 📦 **Download**

```bash
# Composer
composer require dconco/phpspa:^1.1.5

# Or update existing installation
composer update dconco/phpspa
```

**Requirements:**

-  PHP 8.2+
-  [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) v1.1.7+

---

## 🙏 **Acknowledgments**

Special thanks to the phpSPA community for feedback, testing, and contributions that made this release possible!

---

_Built with ❤️ by [Dave Conco](https://github.com/dconco)_

**Full Changelog**: [`v1.1.4...v1.1.5`](https://github.com/dconco/phpspa/compare/v1.1.4...v1.1.5)
