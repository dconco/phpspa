# 🧰 Route Patterns & Param Types

phpSPA’s routing system goes beyond just matching static or dynamic URLs — it supports **route patterns** (like wildcards) and even **typed parameters** (so you can validate values right in the route). Let’s break them down.

---

## 🔀 Route Patterns

Sometimes, you don’t want to match a specific path like `/admin/dashboard` — you want a pattern like `/admin/*` that catches anything under `/admin`.

phpSPA lets you write that like this:

```php
$adminPanel = new Component('Admin');
$adminPanel->route("pattern: /admin/*");
```

> 🧠 When the route starts with `pattern:`, phpSPA switches to **pattern matching mode** using `fnmatch()` behind the scenes.

### 🤔 What can you do with route patterns?

Here are some examples:

| Pattern                 | Matches                                  |
| ----------------------- | ---------------------------------------- |
| `pattern: /admin/*`     | `/admin`, `/admin/users`, `/admin/42`    |
| `pattern: /blog/*.html` | `/blog/post.html`, `/blog/a.html`        |
| `pattern: /files/*.zip` | `/files/latest.zip`, `/files/backup.zip` |

> 🔒 This is super useful for grouping routes, catching unmatched paths, or building admin sections with minimal routing logic.

---

## 🔡 Parameter Types

phpSPA lets you enforce **types** on route parameters — so only valid values match the route. Here’s how it works.

---

### ✅ Basic Typed Param

```php
$profile = new Component('Profile');
$profile->route("/profile/{id: int}");
```

If a user visits `/profile/42`, it matches.
If they visit `/profile/hello`, it doesn’t.

---

### 📚 Supported Param Types

| Type              | Description                                          |
| ----------------- | ---------------------------------------------------- |
| `int` / `integer` | Must be a number like `42`                           |
| `bool`            | Accepts `true`, `false`, `1`, `0`                    |
| `string`          | Any plain text                                       |
| `alpha`           | Only letters (`a-zA-Z`)                              |
| `alphanum`        | Letters and numbers only                             |
| `json`            | Must be valid JSON (decoded)                         |
| `array`           | Accepts JSON arrays (or exploded query-like strings) |

---

### 🤯 Advanced Example

```php
$comp = new Component('Handle');
$comp->route("/data/{info: array<string, array<int, bool>>}");
```

This route matches only if the URL provides a valid array value in `info`, like via a query or a JSON-encoded segment (depending on how you're building the URL). Otherwise, phpSPA skips it.

---

## 🧠 Why Use Typed Params?

There are a few solid reasons:

* ✅ **Cleaner validation** — no need to check `is_numeric()` manually.
* 🔥 **Better routing control** — if types don’t match, the route is skipped entirely.
* ⚠️ **Safer components** — no chance your route logic gets weird data it wasn’t expecting.
* 🎯 **Intentional design** — your routes declare *exactly* what kind of data they’re meant for.

---

## 🛑 What Happens on Mismatch?

If the parameter value doesn’t match the expected type, phpSPA just **ignores the route** — it won’t run the component. This helps prevent accidental matches or invalid behavior.

---

You can use typed params and patterns together too — they’re totally compatible.

---

That’s a wrap on patterns and types!

➡️ [Next: Loading States (Optional but Cool)](./6-loading-states.md)
