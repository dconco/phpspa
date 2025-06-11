# ⏳ Loading Event Hooks

Sometimes when a route is loading — especially over AJAX — you don’t want your users staring at a blank page. That’s where **loading states** come in.

In `phpSPA`, loading states are handled using **event hooks** you can register globally or per-component. These give you full control over UI behaviors during navigation.

---

## 🧮 Global Loading via Events

Hook into the `beforeload` and `load` lifecycle events using:

```js
phpspa.on("beforeload", ({ route }) => {
    // Show a global spinner
});

phpspa.on("load", ({ route, success, error }) => {
    // Hide spinner and handle result
});
```

---

### 📌 Parameters Explained

Each event gives you context about what’s happening:

* `route`: the path being navigated to (string)
* `success`: `true` if the component loaded successfully
* `error`: contains an error object if something went wrong, otherwise `null`

This means you can gracefully handle loading errors, display route-specific logic, or just log transitions.

---

## 🎨 Example with CSS Spinner

```html
<script>
    phpspa.on("beforeload", ({ route }) => {
        const loader = document.createElement("div");
        loader.className = "loader";
        loader.id = "global-loader";
        document.body.appendChild(loader);
        console.log("Navigating to:", route);
    });

    phpspa.on("load", ({ route, success, error }) => {
        document.getElementById("global-loader")?.remove();

        if (!success) {
            console.error("Failed to load:", route);
            alert("Something went wrong loading this page.");
        }
    });
</script>

<style>
.loader {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 30px;
    height: 30px;
    border: 4px solid #ccc;
    border-top-color: #007bff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    z-index: 9999;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
```

---

## 🧩 Per-Component Loading

You can still use `<script type="phpspa/script">` inside a component to define loading behavior that only applies when that component is loaded:

```html
<script type="phpspa/script">
    phpspa.on("beforeload", ({ route }) => {
        // This only runs when this component is being loaded
    });

    phpspa.on("load", ({ success }) => {
        if (!success) {
            alert("Failed to load this view.");
        }
    });
</script>
```

---

## 🔄 When is loading shown?

* Only during **phpSPA navigations**
* Not triggered during **initial page load**

---

You’re not required to define loading states, but they dramatically improve UX — especially on slow networks or large apps.

---

➡️ Up next: [Layout and Content Swap Mechanism](./7-layout-and-content-swap-mechanism.md)
