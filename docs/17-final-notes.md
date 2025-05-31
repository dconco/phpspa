# ✅ Final Notes

That’s a wrap! You’ve now got everything you need to build fast, dynamic, and clean PHP apps using **phpSPA**.

Let’s recap a few things to keep in mind:

---

## 🧠 Core Concepts Recap

* **App setup** is done with `$app = new App(layoutFn);` and uses `__CONTENT__` as the placeholder.
* Use `$app->defaultTargetID()` to set the default render container.
* **Components** are just PHP functions — no magic, no weird syntax.
* Use `$component->route(...)`, `targetID()`, `method()`, and `title()` to configure them.
* Components can have typed route params like `/user/{id: int}` and `$path` will be auto-populated.
* Use `{ComponentName(...)}` syntax to nest components inside others.
* `$path` and `$request` can be passed around and have default values when reusing.
* Use the `<Link />` element or the `Navigate` JS class to move between pages without reloading.

---

## 🛡️ Security

phpSPA handles most internals like route matching, pattern parsing, and param type validation — **you just build**.

But **CSRF** is your responsibility:

* Use `{csrf()}` in your forms.
* Validate it with `if ($request("csrf") === __CSRF__)`.

---

## ⚡ Performance & Behavior Tips

* Use route patterns like `/admin/*` for catch-all logic.
* Use `caseSensitive()` and `caseInSensitive()` to control route casing per component.
* Keep components small and composable.
* Render only what you need in the target container.

---

## 🛠️ Contribute or Explore

* Github: [dconco/phpspa](https://github.com/dconco/phpspa)
* Composer: `dconco/phpspa`
* Issues, bugs, or ideas? PRs and feedback are welcome!

---

## 🚀 What’s Next?

Some ideas to explore next:

* Build reusable UI kits using phpSPA components.
* Connect API endpoints to components via `$request`.
* Write tests for component functions.
* Add progressive enhancements using the built-in JavaScript layer.
