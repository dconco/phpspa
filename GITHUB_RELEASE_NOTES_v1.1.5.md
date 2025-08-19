# 🚀 phpSPA v1.1.5: Performance & Security Powerhouse

## 🎯 **TL;DR**
- **Up to 84% size reduction** with intelligent HTML/CSS/JS compression
- **Enhanced security** with built-in CSRF protection  
- **Class components** with namespace support
- **Method chaining** for fluent configuration
- **Improved PHP-JS integration** with `useFunction()`

---

## ⚠️ **Requirements**
- **JavaScript Engine**: [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) **v1.1.7+** required

```bash
composer update dconco/phpspa:^1.1.5
```

---

## ✨ **Major Features**

### 🗜️ **HTML Compression System**
Intelligent compression with **15-84% size reduction**:

```php
use phpSPA\Compression\Compressor;

$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->run();
```

**Performance Results:**
- HTML + CSS: **41% reduction**
- JavaScript: **40% reduction**  
- Mixed Content: **84% reduction**

### ⚡ **Enhanced PHP-JS Integration**
Direct function calls with improved security:

```php
use function Component\useFunction;

$api = useFunction('getUserData');
// Use in JavaScript: await api(123)
```

### 🏗️ **Class Components**
Object-oriented components with namespace support:

```php
class UserCard {
    public function __render($props) {
        return "<div class='user-card'>{$props['name']}</div>";
    }
}

// Usage: <UserCard name="John" />
```

### 🔗 **Method Chaining**
Fluent API configuration:

```php
$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->attach(require 'components/App.php')
    ->defaultTargetID('app')
    ->cors()
    ->run();
```

### 🛡️ **CSRF Protection**
Built-in security with automatic token management:

```php
// In form: <Component.Csrf name="form-name" />
// Verify: (new Csrf("form-name"))->verify()
```

---

## 🔄 **Breaking Changes**

1. **Namespace Update**: `phpSPA\Component` → `Component`
2. **Script Tags**: Remove `data-type="phpspa/script"` attributes
3. **Layout Files**: Remove `__CONTENT__` placeholders

---

## 📚 **Documentation**

Complete guides available:
- [📖 **Overview**](https://phpspa.readthedocs.io/en/latest/v1.1.5/)
- [🗜️ **Compression**](https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/)
- [⚡ **PHP-JS Integration**](https://phpspa.readthedocs.io/en/latest/v1.1.5/2-php-js-integration/)
- [🏗️ **Class Components**](https://phpspa.readthedocs.io/en/latest/v1.1.5/3-class-components/)
- [🔗 **Method Chaining**](https://phpspa.readthedocs.io/en/latest/v1.1.5/4-method-chaining/)
- [🛡️ **CSRF Protection**](https://phpspa.readthedocs.io/en/latest/v1.1.5/5-csrf-protection/)
- [📋 **Migration Guide**](https://phpspa.readthedocs.io/en/latest/v1.1.5/6-migration-guide/)

---

## 🚀 **Quick Start**

```php
<?php
require 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = (new App(require 'Layout.php'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->attach(require 'components/Dashboard.php')
    ->defaultTargetID('app')
    ->run();
```

---

## 🆘 **Support**

- 📚 [Documentation](https://phpspa.readthedocs.io)
- 🐛 [Issues](https://github.com/dconco/phpspa/issues)
- 💬 [Discord](https://discord.gg/FeVQs73C)
- 🎬 [YouTube](https://youtube.com/@daveconco)

---

**Full Changelog**: [`v1.1.4...v1.1.5`](https://github.com/dconco/phpspa/compare/v1.1.4...v1.1.5)
