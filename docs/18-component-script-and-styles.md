# 🎯 Per-Component Scripts and Styles in PHP

In phpSPA, you can attach custom JavaScript and CSS styles **directly from your PHP components**, and they’ll be injected automatically when that component is rendered.

This helps keep logic **encapsulated per route**, avoiding bloated global files.

---

## 🧠 Why use this?

Because phpSPA swaps out components dynamically on route changes, you might want certain styles or scripts to only load **when their corresponding component is active**.

phpSPA handles that for you by allowing you to define:

* Per-component JavaScript via `$component->script()`
* Per-component CSS via `$component->styleSheet()`

---

## 🧾 Add JavaScript with `$component->script()`

Use this method to define JS logic that should run when this component is rendered.

```php
$comp->script(fn() => <<<JS
    console.log("Dashboard component loaded");

    document.getElementById("refresh").addEventListener("click", () => {
        phpspa.reload();
    });
JS);
```

This script will be wrapped automatically and attached to the DOM **only while this component is active**.

You can call `.script()` multiple times; phpSPA will append them in the order you define.

---

## 🎨 Add Styles with `$component->styleSheet()`

Want to add scoped styles just for one route? Use:

```php
$comp->styleSheet(fn() => <<<CSS
    .dashboard-title {
        font-size: 24px;
        font-weight: bold;
    }

    #refresh {
        margin-top: 10px;
    }
CSS);
```

This CSS will be injected into a `<style data-type="phpspa/css">` block and removed automatically when the component unmounts.

Like with `.script()`, you can call `.styleSheet()` multiple times.

---

## ✨ Example Usage

```php
$dashboard = new Component("Dashboard");

$dashboard->route("/dashboard");
$dashboard->title("Dashboard");

$dashboard->styleSheet(fn() => <<<CSS
    body {
        background-color: #f5f5f5;
    }
CSS);

$dashboard->script(fn() => <<<JS
    console.log("Welcome to the dashboard!");
JS);
```

---

## 🔍 How It Works

Behind the scenes, phpSPA scans for any registered styles and scripts on the server side and outputs them into:

* `<style data-type="phpspa/css">...</style>`
* `<script data-type="phpspa/script">...</script>`

The frontend JavaScript part (`phpspa-js`) handles mounting and cleanup as you navigate between components.

---

➡️ Up next: [Handling Loading State](./19-handling-loading-states.md)
