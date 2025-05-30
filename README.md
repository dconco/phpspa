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

### 🖼️ **Basic Use Case**

```php
<!-- /components/Home.php -->
<template>
  <h1>Welcome Home</h1>
  <p>This is the homepage loaded via phpSPA.</p>
</template>

<script>
  function onMount() {
    echo "Home component mounted.";
  }
</script>
```

---
