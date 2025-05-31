# 🛡️ CSRF Protection

phpSPA helps with most routing and input validation internally, but **CSRF protection** is your job — and it’s simple.

## 🔐 Step 1: Add CSRF Token to Forms

Just include `{csrf()}` anywhere in your form markup:

```php
<form method="POST">
    {csrf()}
    <input name="email" />
    <button type="submit">Send</button>
</form>
```

This will output:

```html
<input type="hidden" name="csrf" value="..." />
```

## ✅ Step 2: Validate on the Server

In your component:

```php
use phpSPA\Http\Request;

function SubmitPage(Request $request) {
    if ($request("csrf") === __CSRF__) {
        // Token is valid — proceed
    } else {
        // Invalid token — reject request
    }
}
```

No additional libraries or sessions are required — phpSPA takes care of generating the token.

---

➡️ Up next: [Final Notes](./17-final-notes.md)
