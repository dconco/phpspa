# 📦 phpSPA v1.0.0 – Initial Release

🚀 *The first official release of phpSPA is here!*

`phpSPA` brings modern Single Page Application (SPA) behavior to native PHP — allowing you to build dynamic, fast-loading, and component-based apps using **pure PHP and a tiny JS layer**. No build tools. No templating engines. Just you and PHP.

---

## ✨ Highlights

* ✅ **Component-Based Architecture** — Define pages as simple PHP functions.
* ✅ **Dynamic Routing** — Easily register GET, POST, or both for each component.
* ✅ **Client-Side Navigation** — URL updates powered by the History API.
* ✅ **State Management (New)** — Server-managed state with client-side updates via `phpspa.setState(...)`.
* ✅ **SEO-Friendly** — Server-rendered first loads, perfect for indexing.
* ✅ **Graceful Fallback** — Works even without JavaScript.
* ✅ **Custom Loaders** — Define per-component or global loading indicators.
* ✅ **Scoped Styles & Scripts** — Add JS/CSS directly inside your components.
* ✅ **Minimal JS Runtime** — Tiny client script with zero dependencies.

---

## 📂 What's Included

* `App` class: Bootstraps your layout and routes.
* `Component` class: Defines routes, methods, targets, and metadata.
* `Request` class: Handles input, files, query params, headers, and auth.
* Full support for nested components and dynamic path parameters (e.g., `/user/{id}`).
* Smart HTML layout placeholders (`__CONTENT__`, `__TITLE__`, meta injection).
* Developer-friendly `<Link />` and history-based navigation (`phpspa.navigate`, `.back()`, `.forward()`).

---

## 🧠 New in v1.0.0

* 🌟 **State Management**:

  * Define state in PHP with `createState('key', default)`.
  * Trigger re-renders from the frontend via `phpspa.setState('key', value)`.
  * Automatically updates server-rendered output in the target container.

* 🧩 **Scoped Component Styles & Scripts**:

  * Use `<style data-type="phpspa/css">...</style>` and `<script data-type="phpspa/script">...</script>` inside your components.
  * Automatically injected and removed during navigation.

* ⚙️ **Improved JS Lifecycle Events**:

  * `phpspa.on("beforeload", callback)`
  * `phpspa.on("load", callback)`

---

## 📦 Installation

```bash
composer require dconco/phpspa
```

Include the JS engine:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

---

## 🧱 Coming Soon

* 🛡️ CSRF protection helpers and automatic verification
* 🧪 Testing utilities for components
* 🌐 Built-in i18n tools

---

## 📘 Docs & Links

* GitHub: [dconco/phpspa](https://github.com/dconco/phpspa)
* JS Engine: [dconco/phpspa-js](https://github.com/dconco/phpspa-js)
* License: MIT

---

💬 Feedback and contributions are welcome!

— Maintained by [Dave Conco](https://github.com/dconco)

---
