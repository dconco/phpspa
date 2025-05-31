# 🧩 Creating Your First Component

In phpSPA, components are the heart of your app. A **component** is just a PHP function that returns a chunk of HTML.

Let’s break this down properly, step by step:

---

## ✅ Step 1: Write a Component Function

Here’s a super simple component:

```php
function Home() {
    return "<h1>Welcome to my site!</h1>";
}
```

> ☝️ A component is just a normal PHP function — nothing special yet.

---

## ✅ Step 2: Register the Component

To make this component load on a specific URL, you’ll wrap it in a `Component` class and give it a route.

```php
use phpSPA\Component;

$home = new Component('Home');
$home->route("/");
```

### 🧠 What’s happening here?

* `Component` accepts **a callable** — you pass the function itself, not what it returns.
* The `route()` method tells phpSPA when to render this component (in this case, at `/`).

> 💡 If your component should respond to a specific HTTP method, you can also add:
>
> ```php
> $home->method("GET");
> ```

And you can also add multiple HTTP method like `GET|POST`, but for now, that’s optional.

---

### 🧱 Optional: targetID() and title()

You can customize how and where the component gets rendered.

```php
$home->targetID("main");  // If you want to use a different element ID than the default
$home->title("Home Page"); // Sets the document title when this component is shown
```

> 🔄 If you don’t call `targetID()`, phpSPA uses the default target set in `$app->defaultTargetID()`.

---

### ✅ All Together Now

Here’s what it looks like as a full example:

```php
function Home() {
    return "<h1>Welcome to my site!</h1>";
}

$home = new Component('Home');
$home->route("/");
$home->method("GET");
$home->title("Home Page");
```

---

### 🔄 What Happens When You Visit `/`?

When a user visits `/`:

* phpSPA matches the route to the `Home` component.
* The `Home()` function runs and returns HTML.
* That HTML replaces the `__CONTENT__` area inside your layout.
* On navigation, only that part updates — no full page reload.

---

➡️ Next up: [Understanding Routing and Parameters](#)

Let me know when you're ready for the routing page or if you want any small additions here.
