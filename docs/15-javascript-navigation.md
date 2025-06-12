# 🧭 JavaScript Navigation: `phpspa.navigate()`

phpSPA provides seamless, JavaScript-powered page transitions without reloads. You can trigger route changes using `phpspa.navigate()` and manage browser history with a few simple helpers.

---

## 📌 Include the JS File

Ensure the phpSPA JavaScript file is loaded in your layout (before the closing `</body>` tag):

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

This enables dynamic routing, component swapping, `<Link />`, state updates, and more.

---

## 🔗 `<Link />` — Inline Navigation Element

To create client-side links without reloading the page, use the custom `<Link />` tag:

```html
<Link to="/login" label="Go to Login" />
```

### ✅ Attributes

| Attribute | Description                            |
| --------- | -------------------------------------- |
| `to`      | (Required) The route to navigate to.   |
| `label`   | (Optional) Text content of the anchor. |

phpSPA automatically renders this as an `<a href="/login">Go to Login</a>` element and intercepts the click event to perform a dynamic transition.

---

## ⚙️ JavaScript Navigation API

Use the global `phpspa` object for manual routing control:

### `phpspa.navigate(path, state = "push")`

Navigate to a new route.

* `path`: The route to go to.
* `state`: `"push"` (default) or `"replace"`.

```js
phpspa.navigate("/dashboard"); // push to history
phpspa.navigate("/login", "replace"); // replace current history
```

### `phpspa.back()`

Go back in browser history:

```js
phpspa.back();
```

### `phpspa.forward()`

Go forward in browser history:

```js
phpspa.forward();
```

### `phpspa.reload()`

Reload the currently mounted component:

```js
phpspa.reload();
```

---

## 📡 Event Hooks: `phpspa.on(...)`

You can listen to lifecycle events using `phpspa.on()`:

```js
phpspa.on("beforeload", ({ route }) => {
  console.log("Loading:", route);
});

phpspa.on("load", ({ route, success, error }) => {
  if (success) {
    console.log("Route loaded:", route);
  } else {
    console.error("Failed to load:", route, error);
  }
});
```

### Event Types

| Event        | Description                                         |
| ------------ | --------------------------------------------------- |
| `beforeload` | Fires before a component fetch starts               |
| `load`       | Fires after a component is loaded or failed to load |

Both events receive an object with:

* `route` → the route being loaded
* `success` → `true` or `false`
* `error` → an error object if `success` is false

---

## 🧩 Component Styles & Scripts

Each component can define its own styles and scripts using these special blocks:

### ✅ Inline Component Style

```html
<style data-type="phpspa/css">
  .page {
    padding: 20px;
  }
</style>
```

### ✅ Inline Component Script

```html
<script data-type="phpspa/script">
  console.log("Component script mounted");
</script>
```

These blocks are handled and injected dynamically by the phpSPA JS runtime whenever components are swapped.

---

➡️ Up next: [CSRF Protection](./16-csrf-protection.md)

Here’s your refined and updated documentation based on your current phpSPA JavaScript system — including your latest changes such as the use of `phpspa.on(...)`, custom `<Link />`, and your `Navigate` class logic:

---
