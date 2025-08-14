# 📦 phpSPA Framework

<div align="center">

**Build modern, component-based PHP applications with SPA-like experience**

[![License](https://img.shields.io/badge/license-MIT-4A90E2?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/packagist/v/dconco/phpspa?style=for-the-badge&color=00D4AA)](https://packagist.org/packages/dconco/phpspa)
[![Downloads](https://img.shields.io/packagist/dt/dconco/phpspa?style=for-the-badge&color=FF6B6B)](https://packagist.org/packages/dconco/phpspa)
[![Documentation](https://img.shields.io/badge/docs-readthedocs-orange?style=for-the-badge)](https://phpspa.readthedocs.io)

</div>

---

## ✨ What is phpSPA?

phpSPA is a **component-based PHP framework** that brings modern frontend development patterns to PHP. Build dynamic, interactive applications using reusable components with no full-page reloads.

🧩 **Component-driven** → Reusable PHP components  
⚡ **Dynamic updates** → No page reloads  
🔗 **Smart routing** → Client + server synced  
🧠 **State management** → Reactive UI updates  
🎯 **Simple syntax** → Pure PHP + HTML  

---

## 🎯 Why Choose phpSPA?

<details>
<summary><strong>🚀 Modern Development Experience</strong></summary>

- **Component-based architecture** like React, but in PHP
- **Instant page transitions** with History API
- **Built-in state management** for reactive UIs
- **Server-side rendering** for SEO optimization
- **Graceful fallback** when JavaScript is disabled

</details>

<details>
<summary><strong>🛠️ Developer-Friendly Features</strong></summary>

- **No template engines** required - pure PHP syntax
- **Composer integration** for easy dependency management
- **Dynamic routing** with GET & POST support
- **Lifecycle hooks** for loading states and metadata
- **Minimal JavaScript** footprint

</details>

---

## 📦 Installation

<details>
<summary><strong>📋 Via Composer (Recommended)</strong></summary>

```bash
composer require dconco/phpspa
```

```php
require 'vendor/autoload.php';
```

</details>

<details>
<summary><strong>⚙️ Manual Installation</strong></summary>

```php
require 'path/to/phpspa/core/App.php';
require 'path/to/phpspa/core/Component.php';
```

</details>

### 🌐 JavaScript Engine

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

---

## 🏗️ Core Concepts

| Concept       | Description                           | Purpose                |
| ------------- | ------------------------------------- | ---------------------- |
| **Layout**    | Base HTML template with `__CONTENT__` | App shell              |
| **Component** | PHP function returning HTML           | Reusable UI pieces     |
| **App**       | Registers components and routes       | Application controller |
| **State**     | Reactive variables across requests    | Dynamic updates        |

---

## 🚀 Quick Start

### 1. Create Layout

```php
<?php
// layout.php
function layout() {
    return <<<HTML
    <html>
        <head>
            <title>My phpSPA App</title>
        </head>
        <body>
            <div id="app">__CONTENT__</div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
        </body>
    </html>
    HTML;
}
```

### 2. Build Components

```php
<?php
// components.php
function HomePage() {
    $counter = createState("count", 0);

    return <<<HTML
        <h1>Counter: {$counter}</h1>
        <button onclick="phpspa.setState('count', {$counter} + 1)">
            Increase
        </button>
        <button onclick="phpspa.setState('count', 0)">
            Reset
        </button>
        <Component.Link to="/login" label="Go to Login" />
    HTML;
}

function LoginPage() {
    return <<<HTML
        <h2>Login</h2>
        <form method="post">
            <input name="username" placeholder="Username">
            <input name="password" type="password" placeholder="Password">
            <button type="submit">Login</button>
        </form>
        <Component.Link to="/" label="Back to Home" />
    HTML;
}
```

### 3. Setup Application

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

## 🧠 State Management

### Creating Reactive State

```php
function CounterComponent() {
    $counter = createState("counter", 0);
    $message = createState("message", "Hello World");
    
    return <<<HTML
        <div>
            <h2>{$message}</h2>
            <p>Count: {$counter}</p>
            <button onclick="phpspa.setState('counter', {$counter} + 1)">
                Increment
            </button>
        </div>
    HTML;
}
```

### JavaScript State Updates

```javascript
// Update state from frontend
phpspa.setState('counter', newValue)
phpspa.setState('message', 'Updated message')

// Listen to state changes
phpspa.on('statechange', ({ key, value }) => {
    console.log(`State ${key} changed to:`, value)
})
```

---

## 🔄 Event System

```javascript
// Loading events
phpspa.on('beforeload', ({ route }) => {
    showLoadingSpinner()
})

phpspa.on('load', ({ success, route }) => {
    hideLoadingSpinner()
    if (success) {
        console.log(`Loaded: ${route}`)
    }
})

// Navigation events
phpspa.on('navigate', ({ from, to }) => {
    console.log(`Navigating from ${from} to ${to}`)
})
```

---

## 🧩 Component Architecture

### Reusable Components

```php
function Button($text, $onClick = '', $class = 'btn') {
    return <<<HTML
        <button class="{$class}" onclick="{$onClick}">
            {$text}
        </button>
    HTML;
}

function Card($title, $content) {
    return <<<HTML
        <div class="card">
            <h3 class="card-title">{$title}</h3>
            <div class="card-content">{$content}</div>
        </div>
    HTML;
}

function UserDashboard() {
    $user = getCurrentUser();
    
    return <<<HTML
        <div class="dashboard">
            {Card('Welcome', "Hello, {$user['name']}!")}
            {Button('Logout', 'logout()', 'btn-danger')}
            <Component.Link to="/profile" label="Edit Profile" />
        </div>
    HTML;
}
```

### Component Composition

```php
function ProductPage() {
    $product = getProduct($_GET['id']);
    
    return <<<HTML
        <div class="product-page">
            {Card($product['name'], $product['description'])}
            {Button('Add to Cart', "addToCart({$product['id']})", 'btn-primary')}
            {Button('Buy Now', "buyNow({$product['id']})", 'btn-success')}
            <Component.Link to="/products" label="← Back to Products" />
        </div>
    HTML;
}
```

---

## 🛠️ Advanced Features

### Dynamic Routing

```php
$app->attach(
    (new Component('ProductDetails'))
        ->title('Product Details')
        ->method('GET')
        ->route('/product/{id}')
        ->loader(function($params) {
            return loadProduct($params['id']);
        })
);
```

### Form Handling

```php
function ContactForm() {
    if ($_POST) {
        $result = processContactForm($_POST);
        if ($result['success']) {
            return '<div class="alert-success">Message sent!</div>';
        }
    }
    
    return <<<HTML
        <form method="post">
            <input name="name" placeholder="Your Name" required>
            <textarea name="message" placeholder="Your Message" required></textarea>
            <button type="submit">Send Message</button>
        </form>
    HTML;
}
```

---

## 📚 Documentation

<div align="center">

**📖 Complete Documentation Available**

[![Read the Docs](https://img.shields.io/badge/Read%20the%20Docs-4A90E2?style=for-the-badge&logo=readthedocs&logoColor=white)](https://phpspa.readthedocs.io)

**Comprehensive guides covering:**

- 🏗️ Component architecture patterns
- 🔄 Advanced routing techniques  
- 🧠 State management strategies
- 🛡️ Security and CSRF protection
- 🧪 Testing and best practices

</div>

---

## 🔗 Links & Resources

| Resource              | Link                                                          |
| --------------------- | ------------------------------------------------------------- |
| **📦 Main Repository** | [dconco/phpspa](https://github.com/dconco/phpspa)             |
| **⚡ JS Engine**       | [dconco/phpspa-js](https://github.com/dconco/phpspa-js)       |
| **📖 Documentation**   | [phpspa.readthedocs.io](https://phpspa.readthedocs.io)        |
| **📦 Packagist**       | [dconco/phpspa](https://packagist.org/packages/dconco/phpspa) |

---

## 🤝 Contributing

<div align="center">

### 💻 Created & Maintained by

<table>
<tr>
<td align="center">

**Dave Conco**  
*Framework Creator*

<img src="https://github.com/dconco.png" width="100" height="100" style="border-radius: 50px; border: 3px solid #4A90E2;"/>

*"Simple, fast, and component-based PHP -*  
*just the way we like it."*

[![GitHub](https://img.shields.io/badge/GitHub-dconco-4A90E2?style=for-the-badge&logo=github)](https://github.com/dconco)

</td>
</tr>
</table>

### 🌟 Show Your Support

**Found phpSPA useful? Give us a star!**

[![Star on GitHub](https://img.shields.io/badge/⭐%20Star%20on%20GitHub-yellow?style=for-the-badge&logo=github)](https://github.com/dconco/phpspa)

Your support helps others discover phpSPA and keeps development going! 🚀

</div>

---

## 📜 License

**MIT License** © [dconco](https://github.com/dconco)

---

<div align="center">

**🎯 Ready to build component-based PHP apps?**

[**Get Started →**](https://phpspa.readthedocs.io) | [**View Examples**](https://github.com/dconco/phpspa/tree/main/examples) | [**Join Community**](https://github.com/dconco/phpspa/discussions)

---

*Built with ❤️ for PHP developers who love modern component architecture*

</div>
