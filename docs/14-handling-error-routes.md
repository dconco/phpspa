# Handling Error Routes

## ❌ Handling Unknown Routes (404 Pages)

In phpSPA, catching unknown or invalid routes is super simple — you just define a route with a **wildcard pattern** using `*`.

---

### 🔧 Example: 404 Component

```php
$notFound = new Component('NotFoundPage');
$notFound->route("pattern: *");
$notFound->title("404 - Page Not Found");
```

This tells phpSPA:

> “If no route matches the current path, fall back to this component.”

You don’t need a separate HTTP response or logic — just register this wildcard route like any other.

---

### 🧠 How It Works

phpSPA uses `fnmatch()` under the hood, so `*` means **“match anything”**. But since this route is checked **last**, it only kicks in if nothing else matches.

This is perfect for:

* 404 Not Found pages
* Maintenance pages
* Fallback layouts

---

### ✅ Example 404 Component

```php
function NotFoundPage() {
    return <<<HTML
       <h1>404</h1>
       <p>Sorry, we couldn't find that page.</p>
       <Link to="/" label="Go Home" />
    HTML;
}
```

---

## 🚫 403 - Forbidden

You can show a **403 page** when users try to access areas they’re not allowed to.

There are two ways to handle this:

---

### 🔹 1. Inside the Component (Recommended)

You can conditionally return a different component if access isn’t allowed:

```php
function AdminPage(Request $request = new Request()) {
    if (!$request("is_admin")) {
        return ForbiddenPage();
    }

    return <<<HTML
        <h1>Admin Panel</h1>
    HTML;
}
```

Your `ForbiddenPage` component could be:

```php
function ForbiddenPage() {
    return <<<HTML
        <h1>403 - Access Denied</h1>
        <p>You don’t have permission to view this page.</p>
    HTML;
}
```

You don’t need to register a route for `ForbiddenPage` if it’s only used internally.

---

### 🔹 2. As Its Own Route

If you want to access a `403` route directly:

```php
$forbidden = new Component('ForbiddenPage');
$forbidden->route("/forbidden");
$forbidden->title("403 - Forbidden");
```

Then in other components, just return `{ForbiddenPage()}` or `Navigate.push('/forbidden')`.

---

## 🛠️ Maintenance Mode (Temporarily Override All Routes)

If you're updating the app and want to show a **maintenance screen for everything**, just use a global route pattern:

```php
$maintenance = new Component('MaintenancePage');
$maintenance->route("pattern: *");
$maintenance->title("We're Updating");
```

This overrides **everything**, including valid routes.

---

### 🔁 Dynamic Toggle

You could add a toggle like this:

```php
if ($maintenanceModeEnabled) {
    $maintenance = new Component('MaintenancePage');
    $maintenance->route("pattern: *");
}
```

So you can control whether the override is active or not.

---

### 🧪 Combining Error Handling

You can even combine patterns:

```php
$notFound->route("pattern: *");        // Fallback for unknown routes
$maintenance->route("pattern: *");     // Overrides all, if enabled
```

Just make sure you register components in the right order — phpSPA picks the **first match**.

---

➡️ Up next: [Javascript Navigation](./15-javascript-navigation.md)
