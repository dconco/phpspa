# 🚀 Getting Started

Welcome! Let's get phpSPA running step by step — no rush, no pressure.

!!! tip "Before You Begin"
    Make sure you have PHP 7.4+ installed on your system. phpSPA works best with modern PHP versions.

## 📦 Installation

=== "Composer (Recommended)"

    If you're using Composer (which you probably should), install phpSPA like this:

    ```bash
    composer require dconco/phpspa
    ```

    !!! success "That's it!"
        phpSPA is now ready to use in your project!

=== "Manual Setup"

    Not using Composer? No problem.

    1. **Download the repository:**
       ```bash
       git clone https://github.com/dconco/phpspa.git
       ```

    2. **Locate the core files:**
       Inside the repo, the two main classes you'll need are:
       - `path/to/phpspa/app/core/App.php`
       - `path/to/phpspa/app/core/Component.php`

    3. **Include them in your project:**
       ```php
        <?php
        require 'path/to/phpspa/core/App.php';
        require 'path/to/phpspa/core/Component.php';
        
        use phpSPA\App;
        use phpSPA\Component;
       ```

    !!! warning "Manual Setup Considerations"
        When using manual setup, you'll need to manage dependencies yourself. Composer is highly recommended for easier maintenance.

## 🧱 The Basic Setup

To use phpSPA, you'll define two main components:

1. **Layout** – the main HTML structure of your page
2. **App instance** – uses your layout and loads components into it

Here's a minimal working example:

```php title="Basic phpSPA Setup"
<?php
use phpSPA\App;

function layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>My phpSPA App</title>
            </head>
            <body>
                <div id="app">
                    __CONTENT__
                </div>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
    HTML;
}

$app = new App('layout');
$app->defaultTargetID('app'); // Optional: defines where dynamic content loads
?>
```

!!! info "Understanding the Code"
    - `layout()` returns the HTML shell of your page
    - `__CONTENT__` is a special placeholder that gets replaced with component HTML
    - `defaultTargetID()` sets the element ID where content updates during navigation

### 🔍 Code Breakdown

| Component           | Purpose                | Required   |
| ------------------- | ---------------------- | ---------- |
| `layout()`          | Defines page structure | ✅ Yes      |
| `__CONTENT__`       | Content placeholder    | ✅ Yes      |
| `defaultTargetID()` | Navigation target      | ❌ Optional |

!!! example "Try It Out"
    Copy the code above into a new PHP file and run it to see phpSPA in action!

## 🎯 Quick Start Checklist

- [ ] Install phpSPA via Composer or manual setup
- [ ] Create your layout function
- [ ] Initialize the App instance
- [ ] Set default target ID (optional)
- [ ] Create your first component

## 🔧 What's Next?

Once your app is set up, you're ready to create dynamic components that make phpSPA shine!

[Continue to: Creating Your First Component :material-arrow-right:](./3-creating-your-first-component.md){ .md-button .md-button--primary }

---

!!! question "Need Help?"
    - Check out our [FAQ section](#)
    - Browse [example projects](#)
    - Join our [community discussions](https://github.com/dconco/phpspa/discussions)
