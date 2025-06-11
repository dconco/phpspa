# ✅ Final Notes

That’s a wrap! You now have everything you need to build fast, dynamic, and modern PHP applications using **phpSPA**.

Here’s a quick summary of the most important concepts:

---

## 🧠 Core Concepts Recap

* Start your app with:

  ```php
  $app = new App(layoutFn);
  $app->defaultTargetID();
  ```

  Use `__CONTENT__` as the render placeholder in your layout.

* Components are regular PHP functions — no special syntax or templating engines.

* Use these methods to configure components:

  * `$component->route("/path")`
  * `$component->targetID("container-id")`
  * `$component->method("GET|POST")`
  * `$component->title("Page Title")`
  * `$component->meta(name: "...", content: "...")`

* Route parameters (e.g. `/user/{id}`) are automatically parsed and passed via `$path`.

* Components can be reused or nested using:

  ```php
  return <<<HTML
     <div>{MyComponent()}</div>
  HTML;
  ```

* Use `$path = []` and `$request = null` as default arguments for reuse safety.

* Frontend navigation uses:

  * Inline `<Link to="/..." label="..." />`
  * JavaScript:

    ```js
    phpspa.navigate("/path");
    phpspa.back();
    phpspa.forward();
    phpspa.reload();
    ```

---

## 🛡️ Security

phpSPA handles:

* Route parsing
* Param type validation
* Internal matching logic

⚠️ **CSRF protection is not included yet.**
It will be added in the next version with full support for tokens and validation helpers.

---

## ⚡ Performance & Behavior Tips

* Use wildcard routes like `/admin/*` for section grouping.
* Control route casing with:

  * `$component->caseSensitive()`
  * `$component->caseInSensitive()`
* Break your app into small components for clarity and speed.
* Use `createState()` for stateful, reactive components.
* Use `$component->script()` and `$component->styleSheet()` to attach per-component JS and CSS.

---

## 🛠️ Contribute or Explore

* GitHub: [dconco/phpspa](https://github.com/dconco/phpspa)
* Composer: `dconco/phpspa`
* Want to help? Feedback and pull requests are always welcome!

---

## 🚀 What’s Next?

Here are some things to try next:

* Build a reusable UI using phpSPA components.
* Store login/session data using state.
* Connect API endpoints and use `$request("key")` to read query/form data.
* Add progressive enhancements using per-component scripts and styles.
