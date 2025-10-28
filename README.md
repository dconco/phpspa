<div align="center">

<img src="https://raw.githubusercontent.com/dconco/dconco/refs/heads/main/phpspa-icon.jpg" alt="PhpSPA - Component-Based PHP Library" style="width: 100%; max-width: 1200px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); margin-bottom: 1rem;">

### _Component-Based PHP Library for Modern Web Applications_

**Build dynamic, interactive web applications using reusable PHP components with state management and SPA-like behavior — no JavaScript frameworks required.**

<br>

[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=for-the-badge)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-read%20the%20docs-blue.svg?style=for-the-badge)](https://phpspa.tech)
[![GitHub stars](https://img.shields.io/github/stars/dconco/phpspa?style=for-the-badge&color=yellow)](https://github.com/dconco/phpspa)
[![PHP Version](https://img.shields.io/packagist/v/dconco/phpspa?style=for-the-badge&color=purple)](https://packagist.org/packages/dconco/phpspa)
[![Downloads](https://img.shields.io/packagist/dt/dconco/phpspa?style=for-the-badge&color=orange)](https://packagist.org/packages/dconco/phpspa)
[![PHP Tests](https://github.com/dconco/phpspa/actions/workflows/php-tests.yml/badge.svg)](https://github.com/dconco/phpspa/actions/workflows/php-tests.yml)

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

_Get up and running in 30 seconds with a complete PhpSPA example_

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

**Step 1: Clone the Template**

```bash
composer create-project phpspa/phpspa my-phpspa-app
cd my-phpspa-app
```

**Step 3: Start Development Server**

```bash
composer start
```

<div align="center">

🎉 **That's it!** Your PhpSPA application is now running locally.

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
    $counter = useState("count", 0);

    return <<<HTML
        <h1>Counter: {$counter}</h1>
        <button onclick="setState('count', {$counter} + 1)">+</button>
        <Component.Link to="/about" children="About" />
    HTML;
}
```

### Setup App

```php
$app = new App(fn() => '<div id="app"></div>');

$app->attach((new Component('HomePage'))->route('/'));
$app->run();
```

---

## 🧪 **Testing**

- Run locally:

```bash
composer install
composer test
```

- CI: Tests run on push/PR to `main` and `dev` via GitHub Actions (see badge above). The entrypoint is `tests/Test.php` which runs the semicolon/ASI suite and a simple compression check.

---

## 🎨 **What You Get**

<table>
<tr>
<td width="50%">
<strong>🧱 Component Architecture</strong><br><br>
Clean, reusable PHP components
</td>
<td width="50%">
<strong>🔄 Reactive State</strong><br><br>
Auto-updating UI with simple state management
</td>
</tr>
<tr>
<td>
<strong>🌍 SPA Navigation</strong><br><br>
Smooth page transitions without reloads
</td>
<td>
<strong>🪶 Lightweight</strong><br><br>
PhpSPA is dependency-free, which makes it extra fast
</td>
</tr>
<tr>
<td>
<strong>🛡️ SEO Ready</strong><br><br>
Server-rendered for search engines
</td>
<td>
<strong>⚙️ Framework Agnostic</strong><br><br>
Works with any PHP setup
</td>
</tr>
</table>

---

## 📚 **Learn More**

🔗 **[Complete Documentation](https://phpspa.tech)** — Full tutorials, examples, and API reference

👉 **[GitHub Repository](https://github.com/dconco/phpspa)** — Source code and issues

📦 **[Packagist](https://packagist.org/packages/dconco/phpspa)** — Installation and versions

---

<br>
<br>

<div align="center">

## ✨ Crafted with Precision By

<a href="https://github.com/dconco">
   <img src="https://raw.githubusercontent.com/dconco/dconco/refs/heads/main/profile3.png" width="150">
</a>

### Dave Conco

_Building modern web applications with the simplicity of PHP_

<!-- This is the interactive badge bar -->
<p align="center">
  <a href="https://github.com/dconco">
    <img src="https://img.shields.io/badge/GitHub-@dconco-181717?style=flat&logo=github&logoColor=white" alt="GitHub">
  </a>
  <a href="https://twitter.com/dave_conco">
    <img src="https://img.shields.io/badge/Twitter-@dave_conco-1DA1F2?style=flat&logo=twitter&logoColor=white" alt="Twitter">
  </a>
  <a href="mailto:concodave@gmail.com">
    <img src="https://img.shields.io/badge/Email-Me%21-D14836?style=flat&logo=gmail&logoColor=white" alt="Email">
  </a>
  <a href="https://dconco.github.io">
    <img src="https://img.shields.io/badge/Website-Portfolio-FF7139?style=flat&logo=Firefox-Browser&logoColor=white" alt="Website">
  </a>
</p>

<!-- This HR is styled with a gradient to match the picture border -->
<hr style="height: 2px; border: none; background: linear-gradient(90deg, transparent, #667eea, #764ba2, transparent); margin: 2rem 0;">

**⭐ If you find PhpSPA useful, please give it a star!**

[![MIT License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

</div>
