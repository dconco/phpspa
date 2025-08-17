<div align="center">

# 🧩 **phpSPA**

### *Component-Based PHP Library for Modern Web Applications*

**Build dynamic, interactive web applications using reusable PHP components with state management and SPA-like behavior — no JavaScript frameworks required.**

<br>

[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.1.7-green.svg?style=for-the-badge)](https://github.com/dconco/phpspa-js)
[![Documentation](https://img.shields.io/badge/docs-read%20the%20docs-blue.svg?style=for-the-badge)](https://phpspa.readthedocs.io)
[![GitHub stars](https://img.shields.io/github/stars/dconco/phpspa?style=for-the-badge&color=yellow)](https://github.com/dconco/phpspa)
[![PHP Version](https://img.shields.io/packagist/v/dconco/phpspa?style=for-the-badge&color=purple)](https://packagist.org/packages/dconco/phpspa)
[![Downloads](https://img.shields.io/packagist/dt/dconco/phpspa?style=for-the-badge&color=orange)](https://packagist.org/packages/dconco/phpspa)

<br>

## ✨ **Key Features**

<table>
<tr>
<td align="center" width="25%">
<strong>🧩 Components</strong><br>
<em>Reusable & Modular</em><br>
Build once, use everywhere
</td>
<td align="center" width="25%">
<strong>🧠 State</strong><br>
<em>Reactive Updates</em><br>
Auto-sync state changes
</td>
<td align="center" width="25%">
<strong>⚡ Performance</strong><br>
<em>Zero Full Reloads</em><br>
SPA-like experience
</td>
<td align="center" width="25%">
<strong>🎯 Simple</strong><br>
<em>Minimal Setup</em><br>
Works out of the box
</td>
</tr>
</table>

</div>

---

## 🚀 **Getting Started**

<div align="center">

### 🎯 **Ready to Jump In? Start with our Template!**

*Get up and running in 30 seconds with a complete phpSPA example*

</div>

<table>
<tr>
<td width="50%" align="center">
<strong>📦 Clone Template</strong><br>
<em>Pre-configured project structure</em>
</td>
<td width="50%" align="center">
<strong>⚡ Instant Setup</strong><br>
<em>Dependencies + server ready</em>
</td>
</tr>
</table>

### **Step 1: Clone the Template**
```bash
git clone https://github.com/mrepol742/phpspa-example my-phpspa-app
cd my-phpspa-app
```

### **Step 2: Install Dependencies**
```bash
composer install
```

### **Step 3: Start Development Server**
```bash
composer start
```

<div align="center">

🎉 **That's it!** Your phpSPA application is now running locally.

**Open your browser and start building amazing components!**

</div>

---

## 🚀 **Quick Start**


### Install

```bash
composer require dconco/phpspa
```

### Create Component

```php
function HomePage() {
    $counter = createState("count", 0);
    return <<<HTML
        <h1>Counter: {$counter}</h1>
        <button onclick="phpspa.setState('count', {$counter} + 1)">+</button>
        <Component.Link to="/about" label="About" />
    HTML;
}
```

### Setup App

```php
$app = new App('layout');
$app->targetId('app');
$app->attach((new Component('HomePage'))->route('/'));
$app->run();
```

---

## 🎨 **What You Get**

<table>
<tr>
<td width="50%">
<strong>🧱 Component Architecture</strong><br>
Clean, reusable PHP components
</td>
<td width="50%">
<strong>🔄 Reactive State</strong><br>
Auto-updating UI with simple state management
</td>
</tr>
<tr>
<td>
<strong>🌍 SPA Navigation</strong><br>
Smooth page transitions without reloads
</td>
<td>
<strong>🪶 Lightweight</strong><br>
Just one small JavaScript file
</td>
</tr>
<tr>
<td>
<strong>🛡️ SEO Ready</strong><br>
Server-rendered for search engines
</td>
<td>
<strong>⚙️ Framework Agnostic</strong><br>
Works with any PHP setup
</td>
</tr>
</table>

---

## 📚 **Learn More**

🔗 **[Complete Documentation](https://phpspa.readthedocs.io)** — Full tutorials, examples, and API reference

👉 **[GitHub Repository](https://github.com/dconco/phpspa)** — Source code and issues

📦 **[Packagist](https://packagist.org/packages/dconco/phpspa)** — Installation and versions

---

<div align="center">

## 👨‍💻 **Created by [Dave Conco](https://github.com/dconco)**

*Building modern web applications with the simplicity of PHP*

<table>
<tr>
<td align="center">
<a href="https://github.com/dconco">
<img src="https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white" alt="GitHub"/>
</a>
</td>
<td align="center">
<a href="https://twitter.com/dave_conco">
<img src="https://img.shields.io/badge/Twitter-1DA1F2?style=for-the-badge&logo=twitter&logoColor=white" alt="Twitter"/>
</a>
</td>
</tr>
</table>

**⭐ If you find phpSPA useful, please give it a star!**

[![MIT License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

</div>
