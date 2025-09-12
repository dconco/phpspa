# Global Asset Management

Comprehensive system for managing application-wide scripts and stylesheets in phpSPA v1.1.7

---

## Overview

The global asset management system allows you to define scripts and stylesheets at the application level that will be automatically included on every component render. This is perfect for:

- **Global utilities** like analytics, error tracking, or polyfills
- **Base styles** like CSS resets, typography, or utility classes
- **Third-party libraries** that need to be available throughout the app
- **Configuration scripts** that set up global variables or settings

## Key Features

### 🌐 Application-Wide Scope
Assets added through `App::script()` and `App::styleSheet()` are automatically included on every component render, ensuring consistency across your entire application.

### 🔗 Session-Based Asset Links
Instead of inlining scripts and styles, the system generates session-based links that:
- Improve performance by enabling browser caching
- Reduce HTML size by avoiding repetitive inline content
- Allow for better compression and minification

### ⚡ Automatic Integration
Global assets are seamlessly integrated with component-specific assets, maintaining the proper loading order and execution context.

---

## Method Reference

### `App::script(callable $script): self`

Adds a global JavaScript function that will be executed on every component render.

**Parameters:**
- `$script` (callable): A function that returns JavaScript code as a string

**Returns:** `self` for method chaining

**Example:**
```php
<?php
$app->script(function() {
    return "
        // Global configuration
        window.APP_CONFIG = {
            version: '1.1.7',
            debug: true,
            apiUrl: '/api'
        };
        
        // Global utility functions
        window.utils = {
            log: function(message) {
                if (window.APP_CONFIG.debug) {
                    console.log('[App]', message);
                }
            },
            
            ajax: function(url, options = {}) {
                return fetch(window.APP_CONFIG.apiUrl + url, options);
            }
        };
    ";
});
```

### `App::styleSheet(callable $style): self`

Adds a global CSS function that will be applied to every component render.

**Parameters:**
- `$style` (callable): A function that returns CSS code as a string

**Returns:** `self` for method chaining

**Example:**
```php
<?php
$app->styleSheet(function() {
    return "
        /* CSS Custom Properties (CSS Variables) */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            
            --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Global reset and base styles */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: var(--font-family-base);
            line-height: 1.6;
            color: #212529;
            background-color: #fff;
        }
        
        /* Utility classes */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            margin-bottom: 0;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            transition: all 0.15s ease-in-out;
        }
        
        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
    ";
});
```

---

## Multiple Assets

You can add multiple global scripts and stylesheets by calling the methods multiple times:

```php
<?php
$app
    // First global stylesheet - CSS reset
    ->styleSheet(function() {
        return "
            /* Normalize.css v8.0.1 | MIT License */
            html { line-height: 1.15; }
            body { margin: 0; }
            main { display: block; }
            /* ... more reset styles ... */
        ";
    })
    
    // Second global stylesheet - utility classes
    ->styleSheet(function() {
        return "
            .text-center { text-align: center; }
            .text-left { text-align: left; }
            .text-right { text-align: right; }
            .d-none { display: none !important; }
            .d-block { display: block !important; }
            .d-inline { display: inline !important; }
            .d-inline-block { display: inline-block !important; }
        ";
    })
    
    // First global script - polyfills
    ->script(function() {
        return "
            // Polyfill for older browsers
            if (!Array.prototype.includes) {
                Array.prototype.includes = function(searchElement) {
                    return this.indexOf(searchElement) !== -1;
                };
            }
        ";
    })
    
    // Second global script - analytics
    ->script(function() {
        return "
            // Google Analytics integration
            gtag('config', 'GA_MEASUREMENT_ID', {
                page_title: document.title,
                page_location: window.location.href
            });
        ";
    });
```

---

## Asset Loading Order

The system maintains a predictable loading order:

1. **Global Stylesheets** (added via `App::styleSheet()`)
2. **Component-specific Stylesheets** (added via `Component::styleSheet()`)
3. **Component HTML Content**
4. **Component-specific Scripts** (added via `Component::script()`)
5. **Global Scripts** (added via `App::script()`)

This ensures that:
- Global styles are available for component styling
- Component styles can override global styles
- Component scripts can use global script utilities
- Global scripts can interact with component content

---

## Best Practices

### 🎯 Use Global Assets For
- **CSS resets and normalize styles**
- **Typography and base styling**
- **Utility classes (grid systems, spacing, etc.)**
- **Global JavaScript utilities and helpers**
- **Third-party library initialization**
- **Analytics and tracking code**
- **Global event handlers**

### ⚠️ Avoid Global Assets For
- **Component-specific styling**
- **Page-specific functionality**
- **Heavy libraries that are only used by some components**
- **Styles that might conflict with component isolation**

### 🔧 Performance Tips
- **Keep global assets lightweight** - They're loaded on every page
- **Use CSS custom properties** for theming instead of repeated declarations
- **Minimize global scripts** - Consider lazy loading for non-critical functionality
- **Leverage caching** by keeping global assets relatively stable

---

## Integration with Components

Global assets work seamlessly with component-specific assets:

```php
<?php
// Global setup
$app
    ->styleSheet(function() {
        return ".container { max-width: 1200px; margin: 0 auto; }";
    })
    ->script(function() {
        return "window.utils = { log: function(msg) { console.log(msg); } };";
    });

// Component using global assets
$app->component('/profile', function() {
    return new Component(function() {
        return "<div class='container'><h1>User Profile</h1></div>";
    })
    ->styleSheet(function() {
        // Component-specific styles that build on global styles
        return ".profile-card { background: white; border-radius: 8px; }";
    })
    ->script(function() {
        // Component script using global utilities
        return "utils.log('Profile component loaded');";
    });
});
```

The resulting page will include both global and component assets in the proper order, creating a cohesive and functional user experience.
