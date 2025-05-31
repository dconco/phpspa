
---

# 📚 phpSPA Documentation

**phpSPA** is a lightweight PHP library for building component-based Single Page Applications using native PHP + HTML. It’s SEO-friendly, fast, and doesn’t require any compilers or frameworks.

---

## 🚀 Introduction

phpSPA gives your PHP apps dynamic SPA behavior without JavaScript frameworks. Instead of reloading full pages, it dynamically swaps only parts of the page using standard PHP functions as components.

---

## 📁 Installation

### 🔸 Composer Installation (Recommended)

```bash
composer require dconco/phpspa
```

Include the autoloader in your entry script:

```php
require 'vendor/autoload.php';
```

### 🔸 Manual Installation

1. Download the `phpSPA` source files (`App.php`, `Component.php`)
2. Place them in a directory like `/phpSPA`
3. In your `index.php`:

```php
require 'part/to/phpspa/App.php';
require 'part/to/phpspa/Component.php';
```

---

## 🧱 Project Structure

A typical project structure might look like:

```
/project-root
  index.php
  layout.php
  /components
    HomePage.php
    Login.php
```

---

## 🧠 Defining the Layout

The layout is a PHP function that returns a full HTML structure.
You **must** include a `__CONTENT__` placeholder where dynamic content will appear.

```php
function layout(): string {
    return <<<HTML
    <html>
        <head><title>phpSPA Example</title></head>
        <body>
            <div id="app">
                __CONTENT__
            </div>
            <script src="/phpspa.min.js"></script>
        </body>
    </html>
HTML;
}
```

---

## 🧩 Creating Components

A component is just a PHP function that returns HTML. It can optionally accept a `$req` object to access request data.

```php
function HomePage(): string {
    return "<h1>Welcome!</h1>";
}

function Login(&$req): string {
    if ($req->method() === "POST") {
        // Handle login
    }
    return <<<HTML
    <form method="POST">
        <input name="username" />
        <input name="password" type="password" />
        <button>Login</button>
    </form>
HTML;
}
```

---

## 🧠 Registering Components

Each component is registered to a route using the `Component` class.

```php
$home = new Component('HomePage');
$home->method = 'GET';
$home->route = '/';

$login = new Component('Login');
$login->method = 'GET|POST';
$login->route = '/login';
```

---

## 🚦 Initializing the App

Now tie it all together with the `App` class:

```php
$app = new App('layout');
$app->targetID('app'); // Default ID to update dynamically

$app->attach($home);
$app->attach($login);

$app->run();
```

---

## ⏳ Loading Indicators

You can define a global loading UI that appears during route changes.

```php
$app->loading(function () {
    return '<script>console.log("Loading...");</script>';
}, true); // true to replace content, false to prepend
```

Each component can also have its **own loader** defined.

---

## 🌐 SEO-Friendly Rendering

Unlike JavaScript-only SPAs, phpSPA renders the initial content server-side. The layout’s `__CONTENT__` is replaced with real component HTML before reaching the browser. This helps with SEO and faster first paint.

---

## 🛠 API Reference

### App

* `new App(callable $layout)`
* `targetID(string $id)`
* `attach(Component $component)`
* `loading(callable $htmlGenerator, bool $replace = false)`
* `run()`

### Component

* `new Component(callable|string $functionName)`
* `route` – route path (e.g. `/login`)
* `method` – HTTP method(s) like `'GET'` or `'GET|POST'`
* `title` *(optional)* – for SEO/title tag
* `targetID` *(optional)* – specific DOM ID to update

---

## 🧪 Example Component with POST

```php
function Feedback(&$req): string {
    if ($req->method() === 'POST') {
        $msg = $req->post('message');
        return "<div>Thanks for your feedback: $msg</div>";
    }

    return <<<HTML
    <form method="POST">
        <textarea name="message"></textarea>
        <button>Send</button>
    </form>
HTML;
}
```

---

## ✅ Why phpSPA?

* Lightweight & zero dependencies
* Fast and SEO-friendly
* Works with traditional PHP projects
* No special templating
* No framework lock-in

---

## 📦 License

MIT © Dconco

---
