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
$app = new App('layout');
```

> `layout` here is the callable, **not** the returned string or a string.

---

## 🎯 Default Target ID (Optional)

By default, phpSPA uses the `__CONTENT__` marker for first render.
But for dynamic navigations (handled by JS), you can define where updates should go:

```php
$app->defaultTargetID("main");
```

> This tells phpSPA’s JS to replace the `<main>` element’s content when changing routes.

---

## 🔀 How Content Swap Works

1. User visits `/dashboard` → server returns layout + Dashboard HTML inserted at `__CONTENT__`.
2. User clicks a link → JS intercepts it.
3. JS sends a background request to the server, asking for just the new component.
4. Once loaded, the content in the `main` tag (or your chosen target ID) is replaced.

All without reloading the whole page. Clean, fast, and good for UX.

---

## 💡 Notes

* You only need to define the layout once — no need to repeat HTML.
* You can style the layout however you want — just keep the `__CONTENT__` placeholder inside.
* If you want to use different target areas for certain components (not the default), we’ll cover that next in **Component Rendering & Target Areas**.

---

➡️ Up next: [Component Rendering & Target Areas](./8-component-rendering-and-target-areas.md)
