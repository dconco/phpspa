# Global Scripts and Styles

Inject application-wide JavaScript and CSS that applies to every component render

---

## Overview

phpSPA v1.1.6 introduces two powerful methods for adding global assets to your application:

- **`App::script()`**: Add JavaScript that executes on every component render
- **`App::styleSheet()`**: Add CSS that applies to every component render

These global assets are rendered alongside component-specific scripts and styles, providing a clean way to manage application-wide functionality and styling.

## Global Scripts with `App::script()`

### Basic Usage

```php
<?php
$app = (new App(require 'Layout.php'))
    ->script(fn() => <<<JS
        console.log("Global script loaded on every component render");
        
        // Global error handling
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
        });
    JS)
    ->attach(require 'components/App.php')
    ->run();
```

### Method Signature

```php
public function script(callable $script): self
```

**Parameters:**
- `$script` (callable): A callable that returns the JavaScript code as a string

**Returns:** The App instance for method chaining

### Multiple Global Scripts

You can add multiple global scripts, and they will execute in the order they were added:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->script(fn() => 'console.log("First global script");')
    ->script(fn() => <<<JS
        // Analytics tracking
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID');
        }
    JS)
    ->script(fn() => <<<JS
        // Global utilities
        window.utils = {
            formatDate: (date) => new Intl.DateTimeFormat().format(date),
            debounce: (func, delay) => {
                let timeoutId;
                return (...args) => {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => func.apply(null, args), delay);
                };
            }
        };
    JS)
    ->attach(require 'components/App.php')
    ->run();
```

## Global Stylesheets with `App::styleSheet()`

### Basic Usage

```php
<?php
$app = (new App(require 'Layout.php'))
    ->styleSheet(fn() => <<<CSS
        /* Global typography */
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        /* Global utility classes */
        .hidden { display: none !important; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1rem; }
    CSS)
    ->attach(require 'components/App.php')
    ->run();
```

### Method Signature

```php
public function styleSheet(callable $style): self
```

**Parameters:**
- `$style` (callable): A callable that returns the CSS code as a string

**Returns:** The App instance for method chaining

### Multiple Global Stylesheets

Add multiple stylesheets that will be combined and rendered:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->styleSheet(fn() => <<<CSS
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    CSS)
    ->styleSheet(fn() => <<<CSS
        /* Theme variables */
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --border-radius: 0.5rem;
        }
    CSS)
    ->styleSheet(fn() => <<<CSS
        /* Component base styles */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
    CSS)
    ->attach(require 'components/App.php')
    ->run();
```

## Combined Usage

Use both global scripts and styles together:

```php
<?php
$app = (new App(require 'Layout.php'))
    ->styleSheet(fn() => <<<CSS
        /* Loading spinner */
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    CSS)
    ->script(fn() => <<<JS
        // Global loading utilities
        window.showLoading = function(element) {
            element.innerHTML = '<span class="loading-spinner"></span> Loading...';
            element.disabled = true;
        };
        
        window.hideLoading = function(element, originalText) {
            element.innerHTML = originalText;
            element.disabled = false;
        };
    JS)
    ->attach(require 'components/App.php')
    ->run();
```

## Use Cases

### 1. Analytics and Tracking

```php
<?php
$app->script(fn() => <<<JS
    // Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'page_view', {
            page_title: document.title,
            page_location: window.location.href
        });
    }
    
    // Custom event tracking
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('track-click')) {
            console.log('Tracked click on:', e.target.textContent);
        }
    });
JS);
```

### 2. Design System Styles

```php
<?php
$app->styleSheet(fn() => <<<CSS
    /* Design system tokens */
    :root {
        --spacing-xs: 0.25rem;
        --spacing-sm: 0.5rem;
        --spacing-md: 1rem;
        --spacing-lg: 1.5rem;
        --spacing-xl: 2rem;
        
        --font-size-sm: 0.875rem;
        --font-size-base: 1rem;
        --font-size-lg: 1.125rem;
        --font-size-xl: 1.25rem;
    }
    
    /* Utility classes */
    .p-xs { padding: var(--spacing-xs); }
    .p-sm { padding: var(--spacing-sm); }
    .p-md { padding: var(--spacing-md); }
    .p-lg { padding: var(--spacing-lg); }
    .p-xl { padding: var(--spacing-xl); }
CSS);
```

### 3. Global Error Handling

```php
<?php
$app->script(fn() => <<<JS
    // Global error handler
    window.addEventListener('error', function(e) {
        console.error('Global error caught:', {
            message: e.message,
            filename: e.filename,
            line: e.lineno,
            column: e.colno,
            error: e.error
        });
        
        // Send to error tracking service
        if (window.errorTracker) {
            window.errorTracker.log(e.error);
        }
    });
    
    // Promise rejection handler
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled promise rejection:', e.reason);
    });
JS);
```

### 4. Theme Management

```php
<?php
$app->styleSheet(fn() => <<<CSS
    /* Dark theme support */
    [data-theme="dark"] {
        --bg-primary: #1a1a1a;
        --text-primary: #ffffff;
        --border-color: #333;
    }
    
    [data-theme="light"] {
        --bg-primary: #ffffff;
        --text-primary: #333333;
        --border-color: #e5e5e5;
    }
    
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.3s, color 0.3s;
    }
CSS);

$app->script(fn() => <<<JS
    // Theme switching functionality
    window.toggleTheme = function() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    };
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
JS);
```

## How Global Assets Are Rendered

### Rendering Order

1. Global stylesheets (in order added)
2. Component-specific stylesheets
3. Global scripts (in order added)
4. Component-specific scripts

### HTML Output

Global assets are rendered with special data attributes:

```html
<!-- Global stylesheet -->
<style data-type="phpspa/css">
    /* Global CSS from App::styleSheet() */
</style>

<!-- Component stylesheet -->
<style data-type="phpspa/css">
    /* Component-specific CSS */
</style>

<!-- Global script -->
<script data-type="phpspa/script">
    /* Global JS from App::script() */
</script>

<!-- Component script -->
<script data-type="phpspa/script">
    /* Component-specific JS */
</script>
```

## Performance Considerations

### Efficient Global Assets

```php
<?php
// ✅ Good: Minimal, focused global assets
$app->styleSheet(fn() => <<<CSS
    /* Only essential global styles */
    .container { max-width: 1200px; margin: 0 auto; }
    .btn { padding: 0.5rem 1rem; border: none; }
CSS);

// ❌ Avoid: Large, bloated global assets
$app->styleSheet(fn() => file_get_contents('large-framework.css'));
```

### Dynamic Content

```php
<?php
// Generate dynamic global content
$theme = $_SESSION['user_theme'] ?? 'light';

$app->styleSheet(fn() => <<<CSS
    :root {
        --primary-color: {$theme === 'dark' ? '#3b82f6' : '#1d4ed8'};
    }
CSS);
```

## Best Practices

### 1. Keep Global Assets Minimal

```php
<?php
// ✅ Good: Essential global functionality only
$app->script(fn() => 'window.APP_VERSION = "1.1.6";');

// ❌ Avoid: Component-specific logic in global scope
$app->script(fn() => 'document.getElementById("login-form").addEventListener(...)');
```

### 2. Use Meaningful Comments

```php
<?php
$app->script(fn() => <<<JS
    // Global utility: Format currency
    window.formatCurrency = function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    };
JS);
```

### 3. Group Related Functionality

```php
<?php
// Group related utilities together
$app->script(fn() => <<<JS
    // Date/Time utilities
    window.dateUtils = {
        format: (date) => new Intl.DateTimeFormat().format(date),
        isToday: (date) => new Date().toDateString() === date.toDateString(),
        addDays: (date, days) => new Date(date.getTime() + days * 24 * 60 * 60 * 1000)
    };
JS);
```

### 4. Method Chaining

```php
<?php
$app = (new App(require 'Layout.php'))
    ->assetCacheHours(24)
    ->styleSheet(fn() => '/* Global styles */')
    ->script(fn() => '/* Global scripts */')
    ->attach(require 'components/App.php')
    ->run();
```

## Troubleshooting

### Scripts Not Executing

If global scripts aren't running:

1. Check browser console for JavaScript errors
2. Verify the callable returns valid JavaScript
3. Ensure scripts are added before `run()` is called

### Styles Not Applying

If global styles aren't working:

1. Check for CSS syntax errors
2. Verify selector specificity
3. Use browser dev tools to inspect rendered CSS
4. Check for conflicting component styles

---

**Previous:** [Asset Cache Management](./asset-cache-management.md) - Configure asset caching behavior