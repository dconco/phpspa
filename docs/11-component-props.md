# 🧬 Component Props (Passing Data to Components)

In phpSPA, since components are just PHP functions, you can pass props the same way you’d pass arguments to functions.

---

## ✅ Basic Example

```php
function Admin(string $name, int $age, array $path = []) {
    return "<h1>$name - $age</h1>";
}
```

Now you can render it inside another component like this:

```php
function Dashboard(array $path) {
    return Admin(name: "dconco", age: 10, path: $path);
}
```

> 🔹 All props passed must match the function’s argument names.
> 🔹 `path` is **optional**, but useful when reusing components tied to a route.

---

## ⚙️ `$path` Special Note

`$path` is automatically injected by phpSPA when the component is matched via a route with dynamic parameters (like `/user/{id}`).

But when **manually reusing a component** inside another one, you must do one of these:

1. Provide a default value:

   ```php
      function Admin(string $name, int $age, array $path = []) { ... }
   ```

2. Pass `$path` explicitly from the parent:

   ```php
   function Dashboard(array $path) {
      return Admin(name: "dconco", age: 10, path: $path);
   }
   ```

---

## 🧪 `$request` Argument

If your component uses `$request`, just define it like this:

```php
use phpSPA\Http\Request;

function Admin(string $name, int $age, array $path = [], Request $request = new Request()) {
    $id = $path["id"] ?? 1;
    $query = $request("q", "default");
    return "<h1>$name - $age - $id</h1>";
}
```

Even though `$request` is auto-injected when the route is hit directly, **you should provide a default value** when reusing the component — that way it won’t break.

> ✅ This is the best practice:

```php
Request $request = new Request()
```

---

## 🔄 Summary: Prop Rules

| Argument                    | Required?          | Default Needed? When?                   |
| --------------------------- | ------------------ | --------------------------------------- |
| Custom Props (e.g. `$name`) | Yes                | Always provide default or pass manually |
| `$path`                     | Provided by phpSPA | Default if reusing manually             |
| `$request`                  | Provided by phpSPA | Always set `new Request()` when reusing |

---

➡️ Up next: [Route Case Sensitivity](./12-route-case-sensitivity.md)
