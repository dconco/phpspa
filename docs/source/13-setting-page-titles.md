# 🏷️ Setting Page Titles

Each component in phpSPA can define its own **page title** — just like you’d set `<title>` in a regular HTML `<head>`. But instead of hardcoding it in your layout, phpSPA lets components control their own titles dynamically.

---

## 🔧 How to Set a Title

Just use the `title()` method on the component:

```php
$login = new Component('LoginPage');
$login->route("/login");
$login->title("Login - phpSPA");
```

Whenever this component is rendered, phpSPA will automatically update the browser’s title bar with what you set here.

---

## 📌 Why It's Useful

* Gives users proper context in the browser tab
* Helps with **SEO**
* Updates correctly on client-side navigation
* Keeps layout file clean and generic

> 💡 You can dynamically set different titles per page, like:

```php
$dashboard->title("Welcome " . $user->name);
```

Just be sure this is done **before** the component renders.

---

➡️ Up next: [Handling Error Routes (404 Pages)](./14-handling-error-routes.md)
