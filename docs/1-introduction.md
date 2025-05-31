# 📘 phpSPA Documentation – Home

## 🏠 Introduction

Welcome to **phpSPA**, a lightweight PHP framework for building dynamic, component-based single-page applications (SPAs) — **without leaving PHP or relying on heavy frontend frameworks**.

phpSPA brings the modern SPA experience (like React or Vue) to standard PHP by **dynamically swapping page content** using custom PHP components, while keeping your existing HTML and PHP workflow.

---

## 🚀 What is phpSPA?

phpSPA is a **pure PHP library** that lets you:

* Write components as standard PHP functions that return HTML.
* Define routes and HTTP methods tied to components.
* Dynamically update a specific section of your page without full reloads.
* Maintain SEO and initial performance with server-side rendering of the first component.
* Handle loading indicators globally or per-component.

All without requiring JavaScript frameworks or a build step.

---

## 🧠 Why Use phpSPA?

If you're a PHP developer who wants:

✅ A modern, dynamic user experience (like SPAs)
✅ To avoid full page reloads on navigation
✅ To stay within the comfort of PHP
✅ No complex build tools, no virtual DOMs
✅ A component-based structure like React — but in PHP

Then **phpSPA is for you**.

---

## 🛠 Key Features

* ✅ Component-based architecture
* ✅ Dynamic content swapping with browser history support
* ✅ Server-side rendering for initial load
* ✅ Route handling by HTTP method (`GET`, `POST`, etc.)
* ✅ Fully customizable layout and loading experience

---

## 📦 Installation

If you’re using Composer (which you probably should), install phpSPA like this:

```bash
composer require dconco/phpspa
```

---

To install manually and include each file manually in your project:

```bash
# Clone the repository into your project
git clone https://github.com/dconco/phpspa.git
```

---

Done ✅

## 👇 Next Steps

➡️ [Getting Started](./2-getting-started.md)
➡️ [Creating Your First Component](./3-creating-your-first-component.md)
➡️ [Understanding Routing and Parameters](./4-routing-and-parameters.md)
