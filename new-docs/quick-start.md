# :rocket: Quick Start

Get phpSPA running in **under 5 minutes**. This guide will have you building reactive PHP components in no time.

---

## :package: **Step 1: Installation**

=== ":material-console: Composer (Recommended)"

    ```bash
    composer require dconco/phpspa
    ```

=== ":material-download: Manual Installation"

    ```bash
    git clone https://github.com/dconco/phpspa.git
    cd phpspa
    ```

!!! tip "PHP Requirements"
    phpSPA requires **PHP 8.2+**. Check your version with `php --version`.

---

## :page_facing_up: **Step 2: Create Your Layout**

Create `layout.php` — this defines your app's HTML structure:

```php title="layout.php"
<?php
function layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>My phpSPA App</title>
            <style>
                body { font-family: system-ui; margin: 0; padding: 20px; }
                .container { max-width: 800px; margin: 0 auto; }
                button { padding: 10px 20px; margin: 5px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div id="app" class="container">
                <!-- Components render here -->
            </div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
        </body>
        </html>
    HTML;
}
```

!!! warning "Important: JavaScript Library"
    Don't forget the `phpspa-js` script — it enables the reactive behavior!

---

## :puzzle: **Step 3: Create Your First Component**

Create `components/counter.php`:

```php title="components/counter.php"
<?php
use function Component\createState;

function Counter() {
    // Create reactive state
    $count = createState('counter', 0);
    $countValue = $count->getValue();
    
    return <<<HTML
        <div class="counter-app">
            <h1>🧮 Counter App</h1>
            <div class="count-display">
                <h2>Count: {$countValue}</h2>
            </div>
            <div class="buttons">
                <button onclick="phpspa.setState('counter', {$countValue} - 1)">
                    ➖ Decrement
                </button>
                <button onclick="phpspa.setState('counter', 0)">
                    🔄 Reset
                </button>
                <button onclick="phpspa.setState('counter', {$countValue} + 1)">
                    ➕ Increment
                </button>
            </div>
            <p>👆 Click buttons to see instant updates!</p>
        </div>
    HTML;
}
```

!!! success "React-like Simplicity"
    Notice how similar this is to React's `useState`? That's the power of phpSPA!

---

## :gear: **Step 4: Set Up Your App**

Create `index.php`:

```php title="index.php"
<?php
require 'vendor/autoload.php';  // If using Composer
require 'layout.php';
require 'components/counter.php';

use phpSPA\App;
use phpSPA\Component;

// Create the app with your layout
$app = new App('layout');

// Register the Counter component
$counter = new Component('Counter');
$counter->route('/');  // Show at homepage

// Register component and run app
$app->attach($counter)->run();
```

---

## :test_tube: **Step 5: Test Your App**

Start PHP's built-in server:

```bash
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) and click the buttons. Notice:

- ✅ **No page reloads** — updates happen instantly
- ✅ **URL stays the same** — true SPA behavior  
- ✅ **State persists** — refresh the page, count remains

---

## :sparkles: **Step 6: Add More Features**

### Add Props to Your Component

```php title="Enhanced Counter with Props"
<?php
function Counter($props = []) {
    $initialValue = $props['start'] ?? 0;
    $step = $props['step'] ?? 1;
    
    $count = createState('counter', $initialValue);
    $countValue = $count->getValue();
    
    return <<<HTML
        <div class="counter-app">
            <h1>Counter (Step: {$step})</h1>
            <h2>Count: {$countValue}</h2>
            <button onclick="phpspa.setState('counter', {$countValue} - {$step})">
                ➖ Decrease by {$step}
            </button>
            <button onclick="phpspa.setState('counter', {$countValue} + {$step})">
                ➕ Increase by {$step}
            </button>
        </div>
    HTML;
}
```

### Register with Props

```php title="Using Component Props"
$counter = new Component('Counter');
$counter->route('/')->props(['start' => 10, 'step' => 5]);
```

### Add Multiple Routes

```php title="Multiple Components & Routes"
// Homepage - Counter
$counter = new Component('Counter');
$counter->route('/');

// About page
$about = new Component(function() {
    return '<h1>About phpSPA</h1><p>Building reactive apps with PHP!</p>';
});
$about->route('/about');

// Attach all components
$app->attach($counter)
    ->attach($about)
    ->run();
```

---

## :books: **What's Next?**

Congratulations! You've built your first phpSPA application. Here's what to explore next:

<div class="next-steps" markdown>

### :material-puzzle: **Learn Core Concepts**

[**Components →**](concepts/components.md){ .md-button }
Learn how to build reusable, composable components

[**State Management →**](concepts/state.md){ .md-button }  
Master reactive state and data flow

[**Routing →**](concepts/routing.md){ .md-button }
Build multi-page applications with navigation

### :material-rocket: **Advanced Features**

[**CSRF Protection →**](security/csrf.md){ .md-button }
Secure your forms and API calls

[**Performance →**](performance/compression.md){ .md-button }
Enable compression and optimization

[**PHP-JS Bridge →**](advanced/php-js-bridge.md){ .md-button }
Call PHP functions from JavaScript

</div>

---

## :question: **Need Help?**

!!! question "Common Issues"

    **Components not updating?**
    Make sure you included the `phpspa-js` script in your layout.

    **State not persisting?**  
    Check that your state keys are unique and properly quoted.

    **Routing not working?**
    Ensure your web server supports URL rewriting (.htaccess for Apache).

### :people_holding_hands: **Get Support**

- [:fontawesome-brands-github: **GitHub Issues**](https://github.com/dconco/phpspa/issues) — Report bugs or ask questions
- [:fontawesome-brands-discord: **Discord Community**](https://discord.gg/FeVQs73C) — Chat with other developers  
- [:material-book-open: **Documentation**](concepts/components.md) — Comprehensive guides and examples

---

!!! success "You're Ready!"
    You now have a working phpSPA application! The component you just built demonstrates the core concepts: state management, reactive updates, and component-based architecture — all in pure PHP.
