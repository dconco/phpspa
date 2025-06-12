# 🌀 Handling a Simple Loading State in phpSPA

In many forms (like login), it's common to show a loading indicator while processing the request. You can use `createState()` in PHP to handle this easily.

---

## ✅ Step 1: Define the State in PHP

```php
use function phpSPA\Component\createState;

$loading = createState('loading', 'false');
```

This creates a state named `"loading"` that defaults to `false`. You can now use `$loading` as a string (`"true"` or `"false"`) to update the UI.

---

## ✅ Bonus: Define the Login State (Optional)

If you're handling the login data too:

```php
$login = createState('login', [
   'username' => null,
   'password' => null
]);

$username = $login()['username'];
$password = $login()['password'];
```

Use that however you want — like verifying login or returning errors.

---

## ✅ Step 2: Render HTML Based on Loading State

Here we update the button's label and disable it when loading:

```php
$loadingText = "$loading" === "true" ? 'Loading...' : 'LOGIN';
$buttonDisabled = "$loading" === "true" ? 'disabled' : '';

$buttonHtml = "<button id=\"btn\" $buttonDisabled>$loadingText</button>";
```

Now render that inside your form:

```php
return <<<HTML
   <form method="POST" action="">
      <label>Username:</label>
      <input type="text" id="username" />

      <label>Password:</label>
      <input type="password" id="password" />

      $buttonHtml
   </form>

   <script data-type="phpspa/script">
      const btn = document.getElementById("btn");

      btn.addEventListener("click", (e) => {
         e.preventDefault();

         const username = document.getElementById("username").value;
         const password = document.getElementById("password").value;

         if (username.trim() !== "" && password.trim() !== "") {
            phpspa.setState("loading", "true")
               .then(() => phpspa.setState("login", { username, password }))
               .then(() => phpspa.setState("loading", "false"));
         }
      });
   </script>
HTML;
```

---

### ✅ Summary

* `createState('loading', 'false')` creates a loading flag.
* You read its value in PHP using `"$loading"`.
* From JS, you update it with `phpspa.setState("loading", "true" | "false")`.
* The UI updates reactively on the next render.

---

➡️ Up next: [Request Handling](./20-request-handling.md)
