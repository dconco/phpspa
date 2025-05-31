# ⏳ Loading States (Optional but Cool)

Sometimes when a route is loading — especially over AJAX — you don’t want your users staring at a blank page. That’s where **loading states** come in.

phpSPA lets you show a loading UI **globally** or per-component, depending on what you want.

---

## 🧮 Global Loading Indicator

You can define a default loading state for the whole app:

```php
$app->defaultLoading(fn() => "<div class='spinner'>Loading...</div>", true);
```

### 📌 Breakdown

* The first argument is a function that returns the loading HTML.
* The second argument is a `bool`:

  * `true` = completely replace the target area with the loading UI.
  * `false` = show loading UI *alongside* existing content.

---

## 🧩 Per-Component Loading

Want something different just for one route? No problem:

```php
$dashboard = new Component('Dashboard');
$dashboard->route("/dashboard");
$dashboard->loading(fn() => "<div class='mini-loader'>Please wait...</div>", false);
```

This loading UI only applies when this component is being fetched.

---

## 🎨 Example with CSS Spinner

Here’s a tiny example you could use:

```php
$app->defaultLoading(function () {
    return <<<HTML
        <div class="loader"></div>
        <style>
        .loader {
            width: 30px;
            height: 30px;
            border: 4px solid #ccc;
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        </style>
    HTML;
}, true);
```

---

## 🔄 When is loading shown?

* Only during **dynamic navigations** (not initial page load).
* It appears **while the component content is being fetched and swapped in**.

---

You’re not required to define a loading state, but it’s a great touch for UX — especially for bigger apps or slow APIs.

---

➡️ Up next: [Layout and Content Swap Mechanism](./7-layout-and-content-swap-mechanism.md)
