# 🧱 Layout and Content Swap Mechanism

At the heart of phpSPA is this:
**One layout. Swappable content. Smooth experience.**

The idea is simple: you define your base HTML layout once, and phpSPA will dynamically update just the main area (without full page reloads) whenever users navigate around.

---

## 🏗️ Define Your Layout

Your layout is just a function that returns HTML (usually with heredoc `<<<HTML` for cleanliness):

```php
function layout() {
    return <<<HTML
    <html>
        <head>
            <title>My App</title>
        </head>
        <body>
            <nav> ... </nav>
            <main>
                __CONTENT__
            </main>
            <script src="https://cdn.example.com/phpspa.js"></script>
        </body>
    </html>
HTML;
}
```

> The special string `__CONTENT__` is where the active component will be inserted by phpSPA.

---

## 🚀 Setting Up the App

Once you’ve got your layout, you can initialize the app:

```php
use phpSPA\App;

$app = new App('layout');
```

> `layout` is a **callable** — the function itself, not its output.

---

## 🎯 Default Target ID (Optional)

By default, phpSPA uses the `__CONTENT__` placeholder for the initial render.
But for dynamic navigations (handled via JavaScript), you can define the DOM element that should be replaced:

```php
$app->defaultTargetID("main");
```

> This tells phpSPA’s JavaScript to replace the `<main>` content when the route changes.

---

## 📦 Add a Component and Run the App

Let’s quickly register a sample component and finish the setup:

```php
use phpSPA\Component;

function Home() {
    return "<h1>Welcome!</h1>";
}

$home = new Component('Home');
$home->route("/");
$home->title("Home");

$app->attach($home);
$app->run();
```

---

## 🔀 How Content Swap Works

1. User visits `/` → phpSPA sends layout + `Home` component inserted at `__CONTENT__`.
2. User clicks a navigation link → JS intercepts it.
3. JS requests just the new component HTML from the server.
4. When it arrives, phpSPA updates the target area (e.g. `<main>`) without touching the rest of the page.

⚡ All without a full page reload. This makes your app feel instant and smooth.

---

## 💡 Notes

* Define your layout once — no need to duplicate markup.
* The `__CONTENT__` placeholder will be swapped with component content automatically.
* You can still control exactly *where* in the DOM the content swaps occur using `targetID()` on each component.

---

➡️ Up next: [Component Rendering & Target Areas](./8-component-rendering-and-target-areas.md)
