# 📬 Request Handling in phpSPA

phpSPA provides a built-in `Request` object that simplifies access to query parameters, form inputs, file uploads, authentication headers, and more.

You can access the request object by adding `$request` as an argument in your component functions:

```php
function LoginComponent($path = [], $request = null) {
   $username = $request("username"); // gets from $_REQUEST['username']
}
```

---

## 🔑 Getting Request Parameters

You can access any form or query input using the `Request` object like a function:

```php
$username = $request("username"); // checks $_REQUEST
$password = $request("password", "default"); // with default fallback
```

This method automatically validates the input for safety.

---

## 📂 Handling File Uploads

Use `$request->files()` to access uploaded files. You can fetch all files or a specific one by name:

```php
$file = $request->files("avatar");

if ($file) {
   move_uploaded_file($file["tmp_name"], "uploads/" . $file["name"]);
}
```

Returns `null` if the file doesn't exist or failed to upload.

---

## 🔐 API Key Validation

If you're building APIs, you can validate API keys directly from headers:

```php
if ($request->apiKey("X-My-Api-Key")) {
   // Valid API key present
}
```

The header name defaults to `'Api-Key'` if not specified.

---

## 🧾 Getting Auth Credentials

The `$request->auth()` method gives you access to Basic Auth or Bearer tokens (from headers):

```php
$auth = $request->auth();

$basicUser = $auth->basic["user"];
$bearerToken = $auth->bearer;
```

Useful for building protected endpoints or user sessions.

---

## 🧭 Parsing Query Parameters

You can also get structured query string data using `urlQuery()`:

```php
$params = $request->urlQuery();       // returns object of all query params
$token = $request->urlQuery("token"); // gets one query param
```

This parses `?key=value` style queries and returns validated values.

---

## ⚠️ CSRF Protection

CSRF protection is **not yet included**. A dedicated token and validation system will be available in the next version.

---

## ✅ Summary

| Feature             | Usage                             |
| ------------------- | --------------------------------- |
| Input parameter     | `$request("key", $default)`       |
| File upload         | `$request->files("input_name")`   |
| API key check       | `$request->apiKey("Header-Name")` |
| Auth (Basic/Bearer) | `$request->auth()`                |
| Parsed URL query    | `$request->urlQuery("key")`       |

---

➡️ Up next: [Final Notes](./final-notes.md)
