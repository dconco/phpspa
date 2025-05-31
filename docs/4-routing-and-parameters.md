# 🧭 Routing and Parameters

phpSPA lets you define clean routes for your components — and yes, you can pass parameters too.

Let’s walk through how routing works, one step at a time.

---

## ✅ Basic Route

You’ve already seen this:

```php
$home = new Component('Home');
$home->route("/");
```

This just tells phpSPA:

> “Render the `Home()` component when the user visits `/`.”

Easy.

---

## 🔢 Dynamic Routes (with Parameters)

You can pass values in the URL using curly braces `{}`.

Example:

```php
$profile = new Component('Profile');
$profile->route("/profile/{id}");
```

Now if someone visits `/profile/42`, the number `42` will be passed to your `Profile` function.

---

## 🧠 How to Access Parameters

phpSPA injects route parameters into your component automatically — using a special `$path` argument.

```php
function Profile(array $path = []) {
    $userId = $path["id"];
    return "<h2>User ID: $userId</h2>";
}
```

### Important Rules

* The argument **must be named** `$path`.
* It **must** be type-hinted as `array` (or just left untyped).
* It can be placed anywhere in your function arguments.
* It has a default value (`= []`) if you are to reuse the component.

---

## 📬 Reading Query Parameters (like `?page=2`)

To access query strings (e.g., `/search?term=apple`), use a `$request` parameter.

```php
use phpSPA\Http\Request;

function Search(array $path = [], Request $request) {
    $term = $request("term");
    return "<h2>Searching for: $term</h2>";
}
```

> ✅ `$request("key")` works like `$_GET["key"]` — but cleaner.

### Rules for `$request`

* The argument **must be named** `$request`.
* It should be type-hinted as `phpSPA\Http\Request` (or just left untyped).
* Like `$path`, it should have a default value if you’re calling it manually or nesting.

---

## 🧩 Nesting Components

If you’re calling a component from inside another component — and the route doesn’t apply — you’ll still need to include these parameters **with default values**, like this:

```php
function MiniCard(array $path = [], Request $request = new Request()) {
    return "<p>This is a reusable UI component.</p>";
}
```

Then call it normally inside another function:

```php
function Dashboard() {
    return MiniCard(); // works fine!
}
```

---

## 🧪 Quick Example

```php
function User(array $path = [], Request $request = new Request()) {
    $id = $path["id"] ?? "unknown";
    $lang = $request("lang");
    
    return "<h1>User $id (Language: $lang)</h1>";
}

$user = new Component('User');
$user->route("/user/{id}");
```

Visit `/user/9?lang=en` and it shows:

```html
<h1>User 9 (Language: en)</h1>
```

---

That’s it for routing — clean and simple.

➡️ [Next: Route Patterns & Param Types](./5-route-patterns-and-param-types.md)
