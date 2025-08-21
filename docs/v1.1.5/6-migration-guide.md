# Migration Guide - v1.1.5

## Overview

This guide helps you migrate from phpSPA v1.1.4 to v1.1.5. While most changes are backward compatible, there are some breaking changes and new features that require attention.

## ⚠️ Breaking Changes

### 1. Namespace Changes

The `\phpSPA\Component` namespace has been changed to `\Component`.

**Before (v1.1.4):**

```php
<?php
use phpSPA\Component\Link;
use phpSPA\Component\useFunction;
use phpSPA\Component\HTMLAttrInArrayToString;
```

**After (v1.1.5):**

```php
<?php
use Component\Link;
use function Component\useFunction;
use Component\HTMLAttrInArrayToString;
```

**Migration Steps:**

1. Update all imports:

```php
<?php
// Find and replace in your codebase:
// OLD: use phpSPA\Component\
// NEW: use Component\

// OLD: use phpSPA\Component\useFunction;
// NEW: use function Component\useFunction;
```

2. Update function calls:

```php
<?php
// OLD: phpSPA\Component\import()
// NEW: Component\import()

// OLD: phpSPA\Component\HTMLAttrInArrayToString()
// NEW: Component\HTMLAttrInArrayToString()
```

### 2. JavaScript Execution Changes

Scripts no longer require `data-type="phpspa/script"` attributes.

**Before (v1.1.4):**

```html
<script data-type="phpspa/script">
	// Your component script
	htmlElement.onclick = () => {
		console.log('Clicked')
	}
</script>
```

**After (v1.1.5):**

```html
<script>
	// Your component script - data-type no longer needed
	htmlElement.onclick = () => {
		console.log('Clicked')
	}
</script>
```

**Migration Steps:**

1. Remove `data-type` attributes from scripts:

```bash
# Find and replace in your project:
# OLD: <script data-type="phpspa/script">
# NEW: <script>
```

2. Update CSS as well:

```bash
# Find and replace:
# OLD: <style data-type="phpspa/css">
# NEW: <style>
```

### 3. Content Rendering Changes

The `__CONTENT__` placeholder has been removed in favor of direct target ID rendering.

**Before (v1.1.4):**

```php
<?php
// Layout.php
return str_replace('__CONTENT__', $content, $layoutTemplate);
```

**After (v1.1.5):**

```php
<?php
// Layout.php - content renders directly to target ID
// No manual replacement needed
return $layoutTemplate;
```

**Migration Steps:**

1. Remove `__CONTENT__` placeholders from layouts:

```php
<?php
// OLD Layout.php
$layout = <<<HTML
<html>
<body>
    <div id="app">__CONTENT__</div>
</body>
</html>
HTML;

return str_replace('__CONTENT__', $content, $layout);
```

```php
<?php
// NEW Layout.php
return <<<HTML
<html>
<body>
    <div id="app"></div>
</body>
</html>
HTML;
```

2. Ensure proper target ID configuration:

```php
<?php
$app = new App('layout')->defaultTargetID('app');
```

## 🆕 New Features Integration

### 1. HTML Compression

Add compression to boost performance:

```php
<?php
use phpSPA\Compression\Compressor;

// Add to existing app
$app = new App('layout')
    ->compression(Compressor::LEVEL_AUTO, true)
    ->attach(require 'components/App.php')
    ->run();
```

### 2. Method Chaining

Modernize your app configuration:

**Before:**

```php
<?php
$app = new App(require 'Layout.php');
$app->attach(require 'components/Login.php');
$app->attach(require 'components/Dashboard.php');
$app->defaultTargetID('app');
$app->cors();
$app->run();
```

**After:**

```php
<?php
$app = (new App(require 'Layout.php'))
    ->attach(require 'components/Login.php')
    ->attach(require 'components/Dashboard.php')
    ->defaultTargetID('app')
    ->cors()
    ->run();
```

### 3. Enhanced PHP-JS Integration

Upgrade your JavaScript-PHP communication:

**Before:**

```php
<?php
function MyComponent() {
    return <<<HTML
    <button onclick="callPhpFunction()">Click me</button>
    <script data-type="phpspa/script">
        function callPhpFunction() {
            phpspa.__call('myFunction', 'arguments').then(result => {
                console.log(result);
            });
        }
    </script>
    HTML;
}
```

**After:**

```php
<?php
use function Component\useFunction;

function MyComponent() {
    $api = useFunction('myFunction');

    return <<<HTML
    <button onclick="callPhpFunction()">Click me</button>
    <script>
        async function callPhpFunction() {
            const result = await {$api('arguments')};
            console.log(result);
        }
    </script>
    HTML;
}
```

### 4. Class Components

Convert function components to classes for better organization:

**Before:**

```php
<?php
function UserCard($name = 'Unknown') {
    return "<div class='user-card'><h3>{$name}</h3></div>";
}
```

**After:**

```php
<?php
class UserCard {
    public function __render($name = 'Unknown') {
        return "<div class='user-card'><h3>{$name}</h3></div>";
    }
}

// Usage remains the same:
echo '<UserCard name="John Doe" />';
```

### 5. CSRF Protection

Add security to your forms:

**Before:**

```php
<?php
function ContactForm() {
    return <<<HTML
    <form method="POST" action="/contact">
        <input type="text" name="name" required>
        <input type="email" name="email" required>
        <button type="submit">Submit</button>
    </form>
    HTML;
}
```

**After:**

```php
<?php
function ContactForm() {
    return <<<HTML
    <form method="POST" action="/contact">
        <Component.Csrf name="contact-form" />

        <input type="text" name="name" required>
        <input type="email" name="email" required>
        <button type="submit">Submit</button>
    </form>
    HTML;
}

// Handle submission
use Component\Csrf;

if ($request->method() === 'POST') {
    $csrf = new Csrf("contact-form");
    if (!$csrf->verify()) {
        die('Invalid CSRF token!');
    }
    // Process form...
}
```

## 📋 Step-by-Step Migration

### Step 1: Update Dependencies

```bash
composer update dconco/phpspa:^1.1.5
```

Update JavaScript engine:

```html
<!-- Update to latest version -->
<script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
```

### Step 2: Fix Namespace Imports

Create a script or use your IDE to find and replace:

```bash
# Using sed (Unix/Linux/macOS)
find . -name "*.php" -exec sed -i 's/use phpSPA\\Component\\/use Component\\/g' {} +
find . -name "*.php" -exec sed -i 's/use phpSPA\\Component\\useFunction/use function Component\\useFunction/g' {} +

# Using PowerShell (Windows)
Get-ChildItem -Path . -Filter *.php -Recurse | ForEach-Object {
    (Get-Content $_.FullName) -replace 'use phpSPA\\Component\\', 'use Component\\' | Set-Content $_.FullName
}
```

### Step 3: Remove Data-Type Attributes

```bash
# Remove data-type attributes from scripts and styles
find . -name "*.php" -exec sed -i 's/<script data-type="phpspa\/script">/<script>/g' {} +
find . -name "*.php" -exec sed -i 's/<style data-type="phpspa\/css">/<style>/g' {} +
```

### Step 4: Update Layout Files

Remove `__CONTENT__` placeholders:

```php
<?php
// Before
function Layout($content) {
    $html = <<<HTML
    <html>
    <body>
        <div id="app">__CONTENT__</div>
    </body>
    </html>
    HTML;

    return str_replace('__CONTENT__', $content, $html);
}

// After
function Layout() {
    return <<<HTML
    <html>
    <body>
        <div id="app"></div>
    </body>
    </html>
    HTML;
}
```

### Step 5: Enable New Features

Add compression and modern configuration:

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

// Old configuration
$app = new App(require 'Layout.php');
$app->attach(require 'components/App.php');
$app->run();

// New configuration with features
$app = (new App(require 'Layout.php'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->attach(require 'components/App.php')
    ->defaultTargetID('app')
    ->run();
```

### Step 6: Test Your Application

1. **Check Console for Errors**: Look for any JavaScript errors after removing data-type attributes
2. **Test Component Rendering**: Ensure components render correctly without `__CONTENT__`
3. **Verify Function Calls**: Test PHP-JS integration with new syntax
4. **Test Form Submissions**: If using forms, add CSRF protection

## 🔧 Common Migration Issues

### Issue 1: Components Not Rendering

**Problem**: Content not appearing after removing `__CONTENT__`

**Solution**: Ensure target ID is properly configured

```php
<?php
// Make sure this matches your layout's container ID
$app->defaultTargetID('app'); // or whatever your container ID is
```

### Issue 2: JavaScript Errors

**Problem**: Scripts not executing after removing data-type attributes

**Solution**: Check for syntax errors in scripts

```javascript
// Make sure your scripts are valid JavaScript
// OLD (might have worked with data-type)
htmlElement.onclick = () => {
	someFunction() // Missing semicolon
}

// NEW (must be valid JavaScript)
htmlElement.onclick = () => {
	someFunction() // Add semicolon
}
```

### Issue 3: Function Call Errors

**Problem**: PHP function calls from JavaScript failing

**Solution**: Update to new useFunction syntax

```php
<?php
// OLD
return <<<HTML
<script data-type="phpspa/script">
    phpspa.__call('myFunction', 'args').then(result => {
        console.log(result);
    });
</script>
HTML;

// NEW
use function Component\useFunction;

$api = useFunction('myFunction');
return <<<HTML
<script>
    {$api('args')}.then(result => {
        console.log(result);
    });
</script>
HTML;
```

### Issue 4: Namespace Errors

**Problem**: Class/function not found after namespace change

**Solution**: Update all imports systematically

```php
<?php
// Check all these patterns in your code:

// OLD patterns to replace:
// phpSPA\Component\Link
// phpSPA\Component\import()
// phpSPA\Component\HTMLAttrInArrayToString()
// phpSPA\Component\useFunction

// NEW patterns:
// Component\Link
// Component\import()
// Component\HTMLAttrInArrayToString()
// function Component\useFunction (note: function keyword for useFunction)
```

## 🧪 Testing Your Migration

### Create a Test Checklist

```php
<?php
// test-migration.php
echo "Testing v1.1.5 Migration:\n\n";

// 1. Test namespace imports
try {
    use Component\Link;
    echo "✅ Namespace imports working\n";
} catch (Error $e) {
    echo "❌ Namespace error: " . $e->getMessage() . "\n";
}

// 2. Test useFunction
try {
    use function Component\useFunction;
    echo "✅ useFunction import working\n";
} catch (Error $e) {
    echo "❌ useFunction error: " . $e->getMessage() . "\n";
}

// 3. Test compression
try {
    use phpSPA\Compression\Compressor;
    if (class_exists('phpSPA\Compression\Compressor')) {
        echo "✅ Compression available\n";
    }
} catch (Error $e) {
    echo "❌ Compression error: " . $e->getMessage() . "\n";
}

// 4. Test CSRF
try {
    use Component\Csrf;
    $csrf = new Csrf('test');
    echo "✅ CSRF protection available\n";
} catch (Error $e) {
    echo "❌ CSRF error: " . $e->getMessage() . "\n";
}

echo "\nMigration test complete!\n";
```

Run this script to verify your migration:

```bash
php test-migration.php
```

## 📚 Additional Resources

-  [Compression System Guide](./1-compression-system.md)
-  [PHP-JS Integration Guide](./2-php-js-integration.md)
-  [Class Components Guide](./3-class-components.md)
-  [Method Chaining Guide](./4-method-chaining.md)
-  [CSRF Protection Guide](./5-csrf-protection.md)

## 🆘 Getting Help

If you encounter issues during migration:

1. **Check Error Logs**: Look for specific error messages
2. **Review Documentation**: Check the feature-specific guides
3. **Community Support**:
   -  [GitHub Issues](https://github.com/dconco/phpspa/issues)
   -  [Discord Community](https://discord.gg/FeVQs73C)
4. **Gradual Migration**: Migrate one component at a time to isolate issues

## 🎯 Post-Migration Optimization

After successful migration, consider these optimizations:

### 1. Enable Compression

```php
<?php
$app->compression(Compressor::LEVEL_AUTO, true);
```

### 2. Convert to Class Components

For better organization and reusability:

```php
<?php
// Consider converting complex function components to classes
class ComplexComponent {
    public function __render(...$props) {
        // Better organization for complex logic
    }
}
```

### 3. Add CSRF Protection

Secure all forms:

```php
<?php
// Add to all forms
echo '<Component.Csrf name="form-name" />';
```

### 4. Use Method Chaining

Cleaner configuration:

```php
<?php
$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->defaultTargetID('app')
    ->run();
```

This migration guide should help you smoothly transition to phpSPA v1.1.5 while taking advantage of all the new features and improvements.
