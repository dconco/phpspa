# 🚀 Getting Started

Welcome! Let’s get phpSPA running step by step — no rush, no pressure.

---

## 📦 Installation

### Option 1: Composer (Recommended)

If you’re using Composer (which you probably should), install phpSPA like this:

```bash
composer require dconco/phpspa
```

Done ✅

---

### Option 2: Manual Setup

Not using Composer? No problem.

1. Download or clone the phpSPA repo from GitHub:

   ```bash
   git clone https://github.com/dconco/phpspa.git
   ```

2. Inside the repo, the two main classes you’ll need are:

   * `path/to/phpspa/app/core/App.php`
   * `path/to/phpspa/app/core/Component.php`

3. Require them manually in your project:

   ```php
   require_once "path/to/phpspa/app/core/App.php";
   require_once "path/to/phpspa/app/core/Component.php";
   ```

---

## 🧱 The Basic Setup

To use phpSPA, you’ll define two things:

1. A **layout** – this is the main HTML structure of your page.
2. An **App instance** – it uses your layout and loads components into it.

Here’s a super basic example:

```php
use phpSPA\App;

function layout() {
    return <<<HTML
        <html>
            <body>
                <div id="app">
                    __CONTENT__
                </div>
            </body>
        </html>
    HTML;
}

$app = new App(layout);
$app->defaultTargetID('app'); // Optional, defines where dynamic content will go
```

### 🧠 What’s happening here?

* `layout()` returns the HTML shell of your page.
* Inside the layout, `__CONTENT__` is a special placeholder. It’ll be replaced with your component’s HTML.
* `defaultTargetID()` sets the element ID where content will update when navigating between components.

---

## 🔧 What’s Next?

Once your app is set up, the next step is to create a component.

➡️ [Continue to: Creating Your First Component](./3-creating-your-first-component.md)
