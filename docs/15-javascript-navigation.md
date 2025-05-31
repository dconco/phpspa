# 🧭 JavaScript Navigation: `Navigate` Class

phpSPA comes with a built-in JavaScript navigation helper that works behind the scenes to handle page transitions without reloading.

## 📌 Important

**You must include the phpSPA JavaScript CDN** inside your layout's HTML. This enables:

* Dynamic routing
* SPA-like transitions
* `<Link />` and `Navigate` class support

```html
<script src="https://cdn.jsdelivr.net/gh/dconco/phpspa@latest/phpspa.js"></script>
```

Place it at the bottom of your layout, before `</body>`.

---

## 🔗 `<Link />` — Navigate Without Reload

Instead of using `<a href="...">`, use the custom `<Link />` element provided by phpSPA.

```html
<Link to="/login" label="Go To Login" />
```

### Attributes

| Attribute | Description                                 |
| --------- | ------------------------------------------- |
| `to`      | Required. The route path to navigate to.    |
| `label`   | Optional. The display text inside the link. |

---

## ⚙️ JavaScript API — `Navigate` Class

The `Navigate` class gives you full control to trigger page transitions manually via JavaScript.

```js
Navigate.push("/dashboard");
```

### 🧭 Methods

#### `Navigate.push(path: string)`

Navigates to a new route and pushes it to the browser history stack.

```js
Navigate.push("/about");
```

#### `Navigate.replace(path: string)`

Replaces the current route without pushing to the history stack.

```js
Navigate.replace("/login");
```

#### `Navigate.pop()`

Moves back in history (like `window.history.back()`).

```js
Navigate.pop();
```

#### `Navigate.reload()`

Reloads the current component manually.

```js
Navigate.reload();
```

#### `Navigate.current()`

Returns the current path.

```js
const path = Navigate.current();
```

---

➡️ Up next: [CSRF Protection](./16-csrf-protection.md)
