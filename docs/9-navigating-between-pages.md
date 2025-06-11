# 🧭 Navigating Between Pages

phpSPA hijacks your navigation to avoid full page reloads — just like modern frontend frameworks. But for that to work properly, you need to do a couple of things.

---

## ⚠️ Include the JavaScript Engine

Make sure to include the phpSPA JS engine in your layout. Add this at the bottom of your layout HTML:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js/dist/phpspa.min.js"></script>
```

> This is required. It’s what powers all dynamic navigation, component swapping, history handling, and more.

---

## 🔗 Use the `<Link />` Element (Recommended)

Instead of a regular `<a>` tag — which reloads the entire page — use the built-in `<Link />` tag:

```html
<Link to="/login" label="Go to Login Page" />
```

### 🔍 Explanation

* `to`: the URL you want to go to.
* `label`: the visible text of the link (you can change this name — more on that below).

> This will trigger a smooth dynamic navigation with no page reload.

---

## 🧠 Bonus: Using JavaScript to Navigate

If you want to navigate programmatically (e.g., inside a click event or custom component), you can use the `Navigate` class provided by phpSPA’s JS.

```js
const router = new Navigate();
router.push("/dashboard");
```

---

### 📘 Available Methods (Suggestions + Design Ideas)

You can implement these:

| Method         | Description                                            | Args             |
| -------------- | ------------------------------------------------------ | ---------------- |
| `push(url)`    | Navigate to a new page and push into history           | `url: string`    |
| `pop()`        | Go back to the previous page in history (like back)    | *(no args)*      |
| `replace(url)` | Navigate to a new page without adding to history stack | `url: string`    |
| `reload()`     | Force reloading the current component again            | *(no args)*      |
| `current()`    | Get the current path                                   | returns `string` |

> These mimic browser-like navigation but stay within the phpSPA environment.

---

## 🔧 Example

```js
document.querySelector("#go-home").onclick = () => {
    const router = new Navigate();
    router.push("/");
};
```

---

## 🧪 Advanced Use (Optional)

You could also build custom buttons like:

```html
<button onclick="(new Navigate()).push('/contact')">Contact Us</button>
```

---

## 📌 Summary

* Use `<Link to="/path" label="..."/>` for clean navigation.
* Use `Navigate` in JS for full programmatic control.
* Don’t forget to add the JS engine script to your layout for any of this to work.

---

➡️ Up next: [Component Nesting (Using Components Inside Components)](./10-component-nesting.md)
