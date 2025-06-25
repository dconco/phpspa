# 📦 **phpSPA - Build Native PHP SPAs Without JavaScript Frameworks**

## 📛 **Name**

**phpSPA** lets you build fast, interactive single-page apps using **pure PHP** — with dynamic routing, component architecture, and no full-page reloads. No JavaScript frameworks required.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![JS Version](https://img.shields.io/badge/version-1.1.2-green.svg)
[![Documentation](https://readthedocs.org/projects/phpspa/badge/?version=latest)](https://phpspa.readthedocs.io)
[![GitHub stars](https://img.shields.io/github/stars/dconco/phpspa?style=social)](https://github.com/dconco/phpspa)
[![PHP Version](https://img.shields.io/packagist/v/dconco/phpspa)](https://packagist.org/packages/dconco/phpspa)
[![Total Downloads](https://img.shields.io/packagist/dt/dconco/phpspa)](https://packagist.org/packages/dconco/phpspa)

---

## 🎯 **Goal**

To empower PHP developers to create **modern, dynamic web apps** with the elegance of frontend SPA frameworks — but fully in PHP.

* 🚫 No full-page reloads
* ⚡ Instant component swapping
* 🧱 Clean, function-based components
* 🌍 Real SPA behavior via History API
* 🧠 Now with **State Management**!

---

## 🧱 **Core Features**

* 🔄 Dynamic content updates — feels like React
* 🧩 Component-based PHP architecture
* 🔗 URL routing (client + server synced)
* 🧠 **Built-in State Management**
* ⚙️ Lifecycle support for loaders, metadata, etc.
* 🪶 Minimal JS: one small file
* 🔁 Graceful fallback (no JS? Still works)

---

## ✨ Features

* ✅ Fully PHP + HTML syntax
* ✅ No template engines required
* ✅ Dynamic GET & POST routing
* ✅ Server-rendered SEO-ready output
* ✅ Per-component and global loading indicators
* ✅ Supports Composer or manual usage
* ✅ **State system**: update UI reactively from JS

---

## 🧠 Concept

* **Layout** → The base HTML (with `__CONTENT__`)
* **Component** → A PHP function returning HTML
* **App** → Registers and runs components based on routes
* **State** → Simple mechanism to manage reactive variables across requests

---

## 🧩 State Management

You can create persistent state variables inside your components using:

```php
$counter = createState("counter", 0);
```

Update state from the frontend:

```js
phpspa.setState("counter", newValue);
```

This will automatically **re-render** the component on update.

---

## 📦 Installation

### 1. Via Composer (Recommended)

```bash
composer require dconco/phpspa
```

Include the autoloader:

```php
require 'vendor/autoload.php';
```

### 2. Manual

Include the core files:

```php
require 'path/to/phpspa/core/App.php';
require 'path/to/phpspa/core/Component.php';
```

---

### 🌐 JS Engine (CDN)

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

---

## 🚀 **Getting Started (with Live Counter)**

```php
<?php
// layout.php
function layout() {
    return <<<HTML
    <html>
        <head>
            <title>My Live App</title>
        </head>
        <body>
            <div id="app">__CONTENT__</div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
        </body>
    </html>
    HTML;
}
```

```php
<?php
// components.php
function HomePage() {
    $counter = createState("count", 0);

    return <<<HTML
        <h1>Counter: {$counter}</h1>
        <button onclick="phpspa.setState('count', {$counter} + 1)">Increase</button>
        <button onclick="phpspa.setState('count', 0)">Reset</button>
        <br><br>
        <Link to="/login" label="Go to Login" />
    HTML;
}

function LoginPage() {
    return <<<HTML
        <h2>Login</h2>
        <form method="post">
            <input name="username" placeholder="Username"><br>
            <input name="password" type="password" placeholder="Password"><br>
            <button type="submit">Login</button>
        </form>
    HTML;
}
```

```php
<?php
// index.php
require 'layout.php';
require 'components.php';

$app = new App('layout');
$app->targetId('app');

$app->attach(
    (new Component('HomePage'))
        ->title('Home')
        ->method('GET')
        ->route('/')
);

$app->attach(
    (new Component('LoginPage'))
        ->title('Login')
        ->method('GET|POST')
        ->route('/login')
);

$app->run();
```

---

## 🛠 JS Events

```js
phpspa.on("beforeload", ({ route }) => showLoader());
phpspa.on("load", ({ success }) => hideLoader());
```

---

## 📚 Full Documentation

Looking for a complete guide to phpSPA?

🔗 **Read the full tutorial and advanced usage on Read the Docs**:

👉 **[https://phpspa.readthedocs.io](https://phpspa.readthedocs.io)**

The docs include:

* 📦 Installation (Composer & Manual)
* 🧩 Component system
* 🔁 Routing & page transitions
* 🧠 Global state management
* ✨ Layouts, nesting, and loaders
* 🛡️ CSRF protection
* 🧪 Practical examples & best practices

Whether you're just getting started or building something advanced, the documentation will walk you through every step.

---

## 📘 Docs & Links

* GitHub: [dconco/phpspa](https://github.com/dconco/phpspa)
* JS Engine: [dconco/phpspa-js](https://github.com/dconco/phpspa-js)
* Website: [https://phpspa.readthedocs.io](https://phpspa.readthedocs.io)
* License: MIT

---

## 📘 License

MIT License © [dconco](https://github.com/dconco)

---

## 🧑‍💻 Maintained by

**Dave Conco**
Simple, fast, and native PHP – just the way we like it.

---

## 🌟 Give Me a Star

If you find phpSPA useful, please consider giving it a star on GitHub! It helps others discover the project and keeps the momentum going 🚀

👉 **[Give us a ⭐ on GitHub](https://github.com/dconco/phpspa)**

Your support means a lot! ❤️

---
