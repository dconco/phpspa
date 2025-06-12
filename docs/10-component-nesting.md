# 🧬 Component Nesting (Using Components Inside Components)

!!! info "Key Concept"
    In phpSPA, **components are just PHP functions** - which means you can call one component inside another just like any normal function.

---

## ✅ Basic Example

```php title="Nesting components like functions"
<?php
function Sidebar() {
    return "<aside>Sidebar content</aside>";
}

function Dashboard(array $path = [], $request = null) {
    return <<<HTML
        <div>
            {{ Sidebar() }}
            <main>Dashboard main content</main>
        </div>
    HTML;
}
```

!!! important "Remember"
    If the nested component accepts `$path` or `$request`, you **must pass them or provide default values**, even if unused.

---

## 🔄 Common Patterns

### Layout Composition Pattern

```php title="Building layouts with nested components"
<?php
use phpSPA\Http\Request;

function Wrapper($path = [], Request $request = new Request()) {
    return <<<HTML
        <div>
           {{ Header() }}
            <section>{MainContent($path, $request)}</section>
           {{ Footer() }}
        </div>
    HTML;
}
```

### Container Components

```php title="Wrapper components with slots"
<?php
function Card($content) {
    return <<<HTML
        <div class="card">
            <div class="card-body">
                {$content}
            </div>
        </div>
    HTML;
}

function UserProfile() {
    return Card(<<<HTML
        <h2>User Details</h2>
        <p>Profile content here</p>
    HTML);
}
```

---

## ⚠️ Critical Notes

```php title="Required parameters example"
<?php
function Profile($path = [], Request $request = new Request()) {
    // Component implementation
}
```

1. **Parameter Inheritance**: Child components needing `$path` or `$request` must declare them
2. **Default Values**: Always provide defaults for optional parameters
3. **Type Safety**: Use type hints for better error handling

---

➡️ **Next Up**: [Component Props (Passing Data to Components) :material-arrow-right:](./11-component-props.md){ .md-button .md-button--primary }
