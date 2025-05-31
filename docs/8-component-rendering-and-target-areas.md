# 🧩 Component Rendering & Target Areas

In phpSPA, each route is tied to a **component**, and every component is just a regular PHP function that returns HTML. You control exactly **where** its output shows up in the page — and how it behaves when dynamically swapped in.

---

## 🛠️ Defining a Component

Here’s a simple component:

```php
function Home() {
    return "<h1>Welcome to the homepage!</h1>";
}
```

To make this available to your app:

```php
$home = new Component('Home');
$home->route("/");
```

> 🔸 `Component` takes the function **callable**, not its return value or a string. So don’t call it — just pass the function itself.

---

## 🎯 Setting a Target Area (Optional)

By default, components get rendered into the **App’s default target area** (`$app->defaultTargetID("main")`, for example).
But if you want a component to render somewhere else (like a specific div), you can override it:

```php
$home->targetID("content");
```

That means this component will update the element with ID `#content` instead of the app’s default.

> ⚠️ Use this when you want a sidebar, modal, or some custom container to be dynamically updated by a route.

---

## 🔁 Full Example

```php
function Login() {
    return "<form>...</form>";
}

$login = new Component('Login');
$login->route("/login");
$login->targetID("main"); // Optional if it's the same as default
```

---

## 🧠 Summary

| What You Can Do         | How                              |
| ----------------------- | -------------------------------- |
| Register a component    | `$comp = new Component('MyFn');` |
| Set its route           | `$comp->route("/about");`        |
| Change where it renders | `$comp->targetID("sidebar");`    |

---

Next up: 📦 [Navigating Between Pages](./9-navigating-between-pages.md)
