# 🧩 Creating Your First Component

In phpSPA, components are the heart of your app. A **component** is just a PHP function that returns a chunk of HTML — no class inheritance or special syntax needed.

!!! success "The Beauty of Simplicity"
    Unlike complex frameworks, phpSPA components are just plain PHP functions. Simple, powerful, and familiar!

Let's walk through the process step by step:

## Step-by-Step Component Creation

### ✅ Step 1: Create the Layout Function

You'll start by defining a layout function that returns the full HTML page. It must include the `__CONTENT__` placeholder, which phpSPA will replace with the component's output.

```php title="Basic Layout Function"
<?php

function layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

!!! note "Essential Placeholder"
    The `__CONTENT__` placeholder is **required** — this is where your component's HTML will be injected.

### ✅ Step 2: Create the App Instance

Now you'll set up the `App` instance and optionally define a default target element ID for components to render into.

```php title="App Initialization"
<?php
use phpSPA\App;

$app = new App(layout);
$app->defaultTargetID("app"); // Optional: default ID for content rendering
```

### ✅ Step 3: Write a Component Function

Here's a super simple component:

=== "Basic Component"

    ```php title="Simple Home Component"
    <?php
    function Home() {
        return "<h1>Welcome to my site!</h1>";
    }
    ```

=== "Enhanced Component"

    ```php title="Rich Home Component"
    <?php
    function Home() {
        return <<<HTML
            <div class="hero-section">
                <h1>Welcome to my site!</h1>
                <p>This is built with phpSPA - simple and powerful!</p>
                <button onclick="alert('Hello phpSPA!')">Say Hello</button>
            </div>
        HTML;
    }
    ```

!!! tip "Component Best Practices"
    A component is just a plain PHP function. No need for special syntax or templating — keep it simple!

### ✅ Step 4: Register the Component

Wrap your function in the `Component` class, and assign it to a route:

```php title="Component Registration"
<?php
use phpSPA\Component;

$home = new Component('Home');
$home->route("/"); // Show this component at the root URL
```

#### Additional Configuration Options

```php title="Advanced Component Configuration"
$home->method("GET");        // Optional: set HTTP method(s)
$home->targetID("main");     // Optional: override the default render target
$home->title("Home Page");   // Optional: sets document.title when shown
```

| Method       | Purpose       | Default     | Required |
| ------------ | ------------- | ----------- | -------- |
| `route()`    | URL pattern   | None        | ✅ Yes    |
| `method()`   | HTTP method   | GET         | ❌ No     |
| `targetID()` | Render target | App default | ❌ No     |
| `title()`    | Page title    | None        | ❌ No     |

!!! info "When to Use targetID()"
    Only use `targetID()` if you want to render into a different element instead of the app's default target.

### ✅ Step 5: Attach and Run

Now, connect the component to the app and start it:

```php title="Final Setup"
$app->attach($home); // Attach the component
$app->run();         // Start the app
```

## 🎯 Complete Working Example

Here's how everything fits together:

```php title="Complete phpSPA App" linenums="1"
<?php
use phpSPA\App;
use phpSPA\Component;

// Define the layout
function layout() {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>My phpSPA App</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .hero-section { text-align: center; padding: 50px 0; }
            </style>
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

// Create a component
function Home() {
    return <<<HTML
        <div class="hero-section">
            <h1>🎉 Welcome to my phpSPA site!</h1>
            <p>This page loads instantly without full page refreshes.</p>
            <button onclick="alert('Hello from phpSPA!')">Try Me!</button>
        </div>
    HTML;
}

// Initialize the app
$app = new App(layout);
$app->defaultTargetID("app");

// Register the component
$home = new Component('Home');
$home->route("/");
$home->method("GET");
$home->title("Home Page");

// Attach and run
$app->attach($home);
$app->run();
?>
```

## 🔄 Understanding the Flow

!!! question "What Happens When You Visit `/`?"

    1. **Route Matching**: The app matches the route to `/`
    2. **Component Rendering**: It executes the `Home()` function
    3. **Content Injection**: The returned HTML replaces `__CONTENT__` in the layout
    4. **SPA Magic**: All without reloading the full page!

User visits / → Route matching → Execute Home() → Replace ****CONTENT**** → Display to user
<pre>  🌐            🔍            ⚙️             🔄                ✅</pre>

## 🚀 Quick Test

To test your component:

1. **Save** the complete example as `index.php`
2. **Run** a local PHP server: `php -S localhost:8000`
3. **Visit** `http://localhost:8000` in your browser
4. **See** your component in action!

## 🔧 What's Next?

Now that you've created your first component, let's explore how to handle different routes and URL parameters.

[Understanding Routing and Parameters :material-arrow-right:](./4-routing-and-parameters.md){ .md-button .md-button--primary }

---

!!! tip "Pro Tips"
    - Keep components focused on a single responsibility
    - Use meaningful function names for your components
    - Consider component reusability from the start
    - Test your components individually before integrating
