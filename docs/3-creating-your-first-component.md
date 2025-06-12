# 🧩 Creating Your First Component

In phpSPA, components are the heart of your app. A **component** is just a PHP function that returns a chunk of HTML — no class inheritance or special syntax needed.

Let’s walk through the process step by step:

---

## ✅ Step 1: Create the Layout Function

You’ll start by defining a layout function that returns the full HTML page. It must include the `__CONTENT__` placeholder, which phpSPA will replace with the component’s output.

```php
function layout() {
    return <<<HTML
        <html>
            <head>
                <title>My phpSPA App</title>
            </head>
            <body>
                <div id="app">
                    __CONTENT__
                </div>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
    HTML;
}
```

---

## ✅ Step 2: Create the App Instance

Now you’ll set up the `App` instance and optionally define a default target element ID for components to render into.

```php
use phpSPA\App;

$app = new App(layout);
$app->defaultTargetID("app"); // Optional: default ID for content rendering
```

---

## ✅ Step 3: Write a Component Function

Here’s a super simple component:

```php
function Home() {
    return "<h1>Welcome to my site!</h1>";
}
```

> ☝️ A component is just a plain PHP function. No need for special syntax or templating.

---

## ✅ Step 4: Register the Component

Wrap your function in the `Component` class, and assign it to a route:

```php
use phpSPA\Component;

$home = new Component('Home');
$home->route("/"); // Show this component at the root URL
```

You can also set:

```php
$home->method("GET");        // Optional: set HTTP method(s)
$home->targetID("main");     // Optional: override the default render target
$home->title("Home Page");   // Optional: sets document.title when shown
```

> 💡 `targetID()` is only needed if you want to render into a different element instead of the app’s default.

---

## ✅ Step 5: Attach and Run

Now, connect the component to the app and start it:

```php
$app->attach($home); // Attach the component
$app->run();         // Start the app
```

---

### ✅ Final Example

Here’s how everything fits together:

```php
use phpSPA\App;
use phpSPA\Component;

function layout() {
    return <<<HTML
    <html>
        <head><title>My phpSPA App</title></head>
        <body>
            <div id="app">
                __CONTENT__
            </div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
        </body>
    </html>
HTML;
}

function Home() {
    return "<h1>Welcome to my site!</h1>";
}

$app = new App(layout);
$app->defaultTargetID("app");

$home = new Component('Home');
$home->route("/");
$home->method("GET");
$home->title("Home Page");

$app->attach($home);
$app->run();
```

---

### 🔄 What Happens When You Visit `/`?

* The app matches the route to `/`
* It renders the `Home()` component
* The returned HTML replaces the `__CONTENT__` part of the layout
* All without reloading the full page

---

➡️ Next up: [Understanding Routing and Parameters](./4-routing-and-parameters.md)
