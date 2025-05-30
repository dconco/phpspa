## 📄 **phpSPA - Project Description**

### 📛 **Name**

**phpSPA** — a lightweight, component-based PHP library for building Single Page Applications (SPAs) without relying on heavy frontend frameworks.

### 🎯 **Goal**

To allow developers to create fast, interactive, and modern PHP websites that behave like SPAs:

* Without full page reloads.
* With dynamic content swapping.
* Using clean, component-based PHP syntax.
* With native URL updates using the History API.

### 🧱 **Core Features**

* 🔄 Dynamic content loading with no full-page reload.
* 🧩 Component-based architecture (like React, but in PHP).
* 🔗 URL routing using JavaScript + PHP routes.
* ⚙️ Lifecycle support (e.g., `onMount`, `onDestroy`).
* 🪶 Minimal JavaScript dependency (one small helper script).
* 🛠️ Works with or without JavaScript (graceful fallback).


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
            <script src="/phpspa.js"></script>
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
            <a href="/login">Go to Login</a>
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

// Register components
$home = new Component('HomePage');
$home->method_get();
$home->route = '/';

$login = new Component('LoginPage');
$login->method_get_post();
$login->route = '/login';

// Initialize the app
$app = new App('layout');
$app->register($home);
$app->register($login);
$app->run();
```
