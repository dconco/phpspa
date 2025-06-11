# 📄 **phpSPA - Project Description**

## 📛 **Name**

**phpSPA** allows developers to build dynamic, component-based PHP applications with modern SPA behavior — without full page reloads. It's designed to feel familiar to frontend devs but stay PHP-native.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Version](https://img.shields.io/badge/version-1.0.0-green.svg)

## 🎯 **Goal**

To allow developers to create fast, interactive, and modern PHP websites that behave like SPAs:

* Without full page reloads.
* With dynamic content swapping.
* Using clean, component-based PHP syntax.
* With native URL updates using the History API.

## 🧱 **Core Features**

* 🔄 Dynamic content loading with no full-page reload.
* 🧩 Component-based architecture (like React, but in PHP).
* 🔗 URL routing using JavaScript + PHP routes.
* ⚙️ Lifecycle support (e.g., `onMount`).
* 🪶 Minimal JavaScript dependency (one small helper script).
* 🛠️ Works with or without JavaScript (graceful fallback).

---

## ✨ Features

* ✅ Full PHP + HTML syntax support — no templating languages or syntax extensions
* ✅ Component-based architecture (just PHP functions returning HTML)
* ✅ Dynamic routing with native PHP
* ✅ SEO-friendly — initial component renders server-side
* ✅ Lightweight JS handles client-side updates
* ✅ Per-component or global loading indicators
* ✅ Works with Composer or manually — no build tools

---

## 🧠 Concept

* **Layout**: A layout function defines the base HTML structure and must include a `__CONTENT__` placeholder.
* **Component**: Each page/component is a PHP function that returns HTML.
* **App**: Manages routing and rendering logic.

---

## ⚙️ Component-Specific Loaders

Each component can also define its **own loader**, to show something unique while it's being fetched. This gives you full control over user experience.

---

## 🧩 Customization

* Supports both `GET`, `POST`, or both: `'GET|POST'`
* You can register as many components as needed.
* Layout can include custom styles/scripts.
* The layout’s `__CONTENT__` will be replaced initially on the server and then updated by JS later.

---

## 📦 Installation

### 1. Via Composer (Recommended)

```bash
composer require dconco/phpspa
```

Include the autoloader in your entry script:

```php
require 'vendor/autoload.php';
```

### 2. Manual Installation

Just clone or download this repo and include it in your project.

In your `index.php`, make sure to include:

```php
require 'path/to/phpspa/core/App.php';
require 'path/to/phpspa/core/Component.php';

use phpSPA\App;
use phpSPA\Component;
```

### 🌐 CDN (for JS Engine)

```html
<script src="https://cdn.jsdelivr.net/gh/dconco/phpspa@main/dist/phpspa.min.js"></script>
```

---

### 🚀 **Getting Started with phpSPA**

```php
<?php
// layout.php
function layout() {
    return <<<HTML
    <html>
        <head>
            <title>phpSPA App</title>
        </head>
        <body>
            <div id="app">
                __CONTENT__
            </div>
            
            <script src="https://cdn.jsdelivr.net/gh/dconco/phpspa@main/dist/phpspa.min.js"></script>
            
            <script>
                phpspa.on("load", ({ success }) => {
                    console.log("Component loaded");
                });
            </script>
        </body>
    </html>
    HTML;
}
```

```php
<?php
// components.php
function HomePage() {
    return <<<HTML
        <div id="home">
            <h1>Welcome to phpSPA</h1>
            <Link to="/login" label="Go to Login" />
        </div>
    HTML;
}

function LoginPage() {
    return <<<HTML
        <div id="login">
            <h2>Login</h2>
            <form method="post">
                <input name="username" placeholder="Username"><br>
                <input name="password" type="password" placeholder="Password"><br>
                <button type="submit">Login</button>
            </form>
        </div>
    HTML;
}
```

```php
<?php
// index.php
require 'layout.php';
require 'components.php';

// Initialize the app
$app = new App('layout');
$app->targetId('app');

// Register components
$home = new Component('HomePage');
$home->title = 'Home Page';
$home->method = 'GET';
$home->route = '/';

$login = new Component('LoginPage');
$login->title = 'Login Page';
$login->method = 'GET|POST';
$login->route = '/login';

// Attach Components and run application
$app->attach($home);
$app->attach($login);
$app->run();
```

---

## 🛠 Events

```js
phpspa.on("beforeload", ({ route }) => showLoader());
phpspa.on("load", ({ success }) => hideLoader());
```

---

## 📘 License

MIT © [dconco](https://github.com/dconco)

---

## 🛠 Maintained by

**Dave Conco**
Simple, fast, and native PHP – just the way we like it.

---
