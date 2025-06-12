# 🧩 Component Rendering & Target Areas

In phpSPA, each route is tied to a **component**, and every component is just a regular PHP function that returns HTML. You control exactly **where** its output shows up in the page — and how it behaves when dynamically swapped in.

---

## 🛠️ Defining a Component

Here's a simple component:

```php
<?php
function Home() {
    return "<h1>Welcome to the homepage!</h1>";
}
```

To make this available to your app:

```php
<?php
use phpSPA\Component;
$home = new Component('Home');
$home->route("/");
```

!!! info "Component Function Tip"
    `Component` takes the function **callable**, not its return value or a string. So don't call it — just pass the function itself.

---

## 🔢 Specifying HTTP Methods (Optional)

You can control **which HTTP methods** the component responds to:

```php
$home->method("GET");
```

You can also allow **multiple methods** using a pipe `|` separator:

```php
$login->method("GET|POST");
```

!!! tip "Form Handling"
    This is especially useful for components that handle form submissions (`POST`) and normal page loads (`GET`).

---

## 📄 Setting Page Titles (Optional)

Want to change the `<title>` when this component is loaded?

```php
$home->title("Home Page");
```

!!! note "Automatic Title Updates"
    This automatically updates `document.title` in the browser when this component is shown.

---

## 🎯 Setting a Target Area (Optional)

By default, components render into the app's default target area (e.g., set using `$app->defaultTargetID("main")`).

But if you want a component to render elsewhere (like a specific div), override it:

```php
$home->targetID("content");
```

That tells phpSPA to update the element with ID `#content` when this route loads.

!!! warning "Custom Layout Usage"
    Use this for modals, sidebars, or custom layout sections.

---

## 🔁 Full Example

Here's everything put together:

```php title="Complete Component Setup"
<?php
use phpSPA\Component;

function Login() {
    return "<form method='post'>...</form>";
}

$login = new Component('Login');
$login->route("/login");
$login->method("GET|POST");
$login->title("Login Page");
$login->targetID("main"); // Optional if it matches default
```

---

## 🧠 Quick Reference

| Action               | Method            | Example                          |
| -------------------- | ----------------- | -------------------------------- |
| Register a component | `new Component()` | `$comp = new Component('MyFn');` |
| Set its route        | `->route()`       | `$comp->route("/about");`        |
| Allow HTTP methods   | `->method()`      | `$comp->method("GET\|POST");`    |
| Set browser title    | `->title()`       | `$comp->title("Page Title");`    |
| Change render target | `->targetID()`    | `$comp->targetID("sidebar");`    |

!!! example "Common Patterns"
    === "Basic Page"
        ```php
        <?php
        $home = new Component('Home');
        $home->route("/");
        $home->title("Welcome");
        ```

    === "Form Handler"
        ```php
        <?php
        $contact = new Component('ContactForm');
        $contact->route("/contact");
        $contact->method("GET|POST");
        $contact->title("Contact Us");
        ```
    
    === "Modal Content"
        ```php
        <?php
        $modal = new Component('ProfileModal');
        $modal->route("/profile/edit");
        $modal->targetID("modal-container");
        ```

---

!!! success "What's Next?"
    Ready to build more complex layouts? Check out **[Component Nesting](./10-component-nesting.md)** to learn how components can work together!
