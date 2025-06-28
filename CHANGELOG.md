# CHANGELOG

## v1.1.2

- Made `route` method ro br optional, if route is not provided for each component, it'll render it, provided the request method matches the component method.

## v1.1.1

Fixes Bugs and Errors.

## v1.1.0

- Added file import `phpSPA\Component\import()` function for importing files (images) to html. @see [File Import Utility](https://phpspa.readthedocs.io/en/latest/v1.1/1-file-import-utility)

- Added `map()` method to state management, can now map array to html elements, `$stateItems->map(fn (item) => "<li>{$item}</li>")`. @see [Mapping In State Management](https://phpspa.readthedocs.io/en/latest/v1.1/2-mapping-in-state-management)

- Added component to be accessible by html tags, `<Component />`, both inline tags and block tags `<Component></Component`. @see [Using Component Functions By HTML Tags](https://phpspa.readthedocs.io/en/latest/v1.1/3-using-component-functions-by-html-tags)

- Created component function `<Link />`, and made it be under the `phpSPA\Component` namespace. @see [Link Component](https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component)

- Added `phpSPA\Component\HTMLAttrInArrayToString()` function, use it when converting `...$props` rest properties in a component as rest of HTML attributes. @see [HTML Attribute In Array To String Conversion](https://phpspa.readthedocs.io/en/latest/v1.1/5-html-attr-in-array-to-string-function)

- Added function `phpSPA\Http\Redirect()` for redirecting to another URL. @see [Redirect Function](https://phpspa.readthedocs.io/en/latest/v1.1/6-redirect-function.md)

- Created component function `<PhpSPA.Component.Navigate />`, for handling browser's navigation through PHP. @see [Navigate Component](https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md)

- Made JS `phpspa.setState()` available as just `setState()` function.

### Deprecated

- Using HTML `<Link />` tag without the function namespace is deprecated. You must use the namespace in other to use the component function, `<PhpSPA.Component.Link />` See: [Deprecated HTML Link](https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component/#deprecated)

---

## v1.0.0 - Initial Release

### 🧠 New in v1.0.0

- 🌟 **State Management**:

  - Define state in PHP with `createState('key', default)`.
  - Trigger re-renders from the frontend via `phpspa.setState('key', value)`.
  - Automatically updates server-rendered output in the target container.

- 🧩 **Scoped Component Styles & Scripts**:

  - Use `<style data-type="phpspa/css">...</style>` and `<script data-type="phpspa/script">...</script>` inside your components.
  - Automatically injected and removed during navigation.

- ⚙️ **Improved JS Lifecycle Events**:

  - `phpspa.on("beforeload", callback)`
  - `phpspa.on("load", callback)`

---

## 📦 Installation

```bash
composer require dconco/phpspa
```

Include the JS engine:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

---

## 🧱 Coming Soon

- 🛡️ CSRF protection helpers and automatic verification
- 🧪 Testing utilities for components
- 🌐 Built-in i18n tools

---

## 📘 Docs & Links

- GitHub: [dconco/phpspa](https://github.com/dconco/phpspa)
- JS Engine: [dconco/phpspa-js](https://github.com/dconco/phpspa-js)
- Website: [https://phpspa.readthedocs.io](https://phpspa.readthedocs.io)
- License: MIT

---

💬 Feedback and contributions are welcome!

— Maintained by [Dave Conco](https://github.com/dconco)
