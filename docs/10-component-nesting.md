# 🧬 Component Nesting (Using Components Inside Components)

You can call one component inside another just like a normal function — because in phpSPA, **components are just PHP functions**.

---

## ✅ Example

```php
function Sidebar() {
    return "<aside>Sidebar content</aside>";
}

function Dashboard(array $path = [], $request = null) {
    return <<<HTML
        <div>
            {Sidebar()}
            <main>Dashboard main content</main>
        </div>
    HTML;
}
```

Just make sure if the component you're nesting accepts `$path` or `$request`, **you pass them or give default values**, even if you won’t use them.

---

## 🔄 Common Patterns

You might see patterns like:

```php
use phpSPA\Http\Request;

function Wrapper($path = [], Request $request = new Request()) {
    return <<<HTML
        <div>
            {Header()}
            <section>{MainContent($path, $request)}</section>
            {Footer()}
        </div>
    HTML;
}
```

> This is how you build layouts or reusable containers.

---

## ⚠️ Don’t Forget

If the nested component relies on `$path` or `$request`, they **must be defined in its argument list**, even if empty. Example:

```php
function Profile($path = [], Request $request = new Request()) { ... }
```

---

➡️ Up next: [Component Props (Passing Data to Components)](./11-component-props.md)
