# The Request Object: In-Depth

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

The `Request` object is your primary tool for interacting with incoming data. Beyond the basic methods like `post()` and `get()`, it offers several powerful shortcuts and helpers to make your code more concise and readable.

!!! info "Universal Access"
    The Request object provides unified access to all types of input data.

## Universal Parameter Access

Instead of checking `post()`, `get()`, and `json()` separately, you can use the `Request` object like a function to get a parameter from **any** input source (`$_REQUEST`). This is the quickest way to access data.

```php
<?php
use PhpSPA\Http\Request;

function SearchHandler(Request $request) {
   // This will get the 'term' value whether it comes from
   // a GET query string (?term=...) or a POST form field.
   $searchTerm = $request('term');

   // ... perform search logic ...
   return "<p>Showing results for: <strong>{$searchTerm}</strong></p>";
}
```

!!! tip "Flexible Input"
    This will get the 'term' value whether it comes from a GET query string (?term=...) or a POST form field.

## Handling File Uploads

Accessing uploaded files is simple with the `->files()` method.

=== "All Files"

    Calling `$request->files()` with no arguments returns an array of all uploaded files.

    ```php
    <?php

    $allFiles = $request->files();
    ```

=== "Specific File"

    Calling it with a name returns the data for that specific file input.

    ```php
    <?php

    $avatarFile = $request->files('avatar');
    ```

!!! example "File Upload Handler"

```php
<?php

use PhpSPA\Http\Request;

function ProfileUpload(Request $request) {
   if ($request->isMethod('POST')) {
      $avatarFile = $request->files('avatar');

      if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
         // A file was successfully uploaded
         $tmpName = $avatarFile['tmp_name'];
         $fileName = basename($avatarFile['name']);
         move_uploaded_file($tmpName, "uploads/{$fileName}");

         return "<p>File uploaded successfully!</p>";
      }
   }

   return <<<HTML
      <form method="POST" enctype="multipart/form-data">
         <input type="file" name="avatar">
         <button type="submit">Upload</button>
      </form>
   HTML;
}
```

!!! success "Secure Upload"
    Always validate file uploads on the server-side before processing them.

---

## Raw Request Body

Use `getContent()` to read the raw, unparsed request payload exactly as the client sent it. It reads from `php://input`, so it works for **any** HTTP method and **any** content type — JSON, XML, or anything else.

```php
<?php

// Raw body as sent by the client
$raw = $request->getContent();

// Decode a JSON payload manually
$data = json_decode($request->getContent(), true);
```

!!! info "Read Once, Cached"
    `php://input` can only be read once per request. `getContent()` reads it a single time and caches the result, so every later call — and every method that relies on it, such as `json()` and `form()` — returns the cached body instead of touching the stream again. Repeated calls are cheap.

!!! warning "Check for `false`"
    If the input stream cannot be read, `getContent()` returns `false` (not an empty string). Guard for it when the body is required:

    ```php
    <?php

    $raw = $request->getContent();
    if ($raw === false) {
        // handle the unreadable body
    }
    ```

---

## Request Helper Methods

The Request object provides several helper methods for common operations:

### **Method Checking**

```php
<?php

// Check if request method matches
if ($request->isMethod('POST')) {
    // Handle POST request
}

// Get the current HTTP method
$method = $request->method(); // 'GET', 'POST', 'PUT', etc.
```

### **Protocol & Security**

```php
<?php

// Check if request is over HTTPS
if ($request->isHttps()) {
    // Secure connection
}

// Get server protocol
$protocol = $request->protocol(); // 'HTTP/1.1', 'HTTP/2', etc.

// Get request URI
$uri = $request->getUri(); // '/api/users/123'
```

### **Request Metadata**

```php
<?php

// Get request timestamp
$timestamp = $request->requestTime();

// Get content type
$contentType = $request->contentType(); // 'application/json'

// Get client IP address
$ip = $request->ip();

// Check if AJAX/XHR request
if ($request->isAjax()) {
    // Handle async request
}

// Get referrer URL
$referrer = $request->referrer();
```

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `isMethod(string $method)` | `bool` | Checks if request method matches |
| `method()` | `string` | Returns HTTP method (GET, POST, etc.) |
| `isHttps()` | `bool` | Checks if request is over HTTPS |
| `protocol()` | `?string` | Returns server protocol |
| `getUri()` | `string` | Returns request URI |
| `requestTime()` | `int` | Returns Unix timestamp of request |
| `contentType()` | `?string` | Returns Content-Type header value |
| `ip()` | `string` | Returns client IP address |
| `isAjax()` | `bool` | Checks if request is AJAX/XHR |
| `referrer()` | `?string` | Returns referring URL |
| `getContent()` | `string\|false` | Returns the raw request body (cached), or `false` if unreadable |

---

## Reading Input Data

Beyond the universal `$request('key')` accessor, each input source has a dedicated method. All of them return the whole collection when called with no argument, or a single value by key.

### **Query, POST & Cookies**

```php
<?php

// Query string ($_GET)
$all   = $request->get();         // all GET params
$page  = $request->get('page');   // single value, or null

// Native POST body ($_POST)
$all   = $request->post();        // all POST params
$name  = $request->post('name');  // single value, or null

// Cookies ($_COOKIE)
$all   = $request->cookie();       // all cookies
$theme = $request->cookie('mode'); // single value, or null
```

### **Session**

`session()` starts the session automatically before reading. Call with no argument for the full `$_SESSION` array, or a key for a single value.

```php
<?php

$all   = $request->session();        // entire session
$user  = $request->session('user');  // single value, or null
```

### **JSON Bodies**

`json()` decodes the raw body (via `getContent()`) as JSON. Returns the decoded array, a single key, or `null` if the body is empty or not valid JSON.

```php
<?php

$data = $request->json();          // ['name' => 'PhpSPA', ...] or null
$name = $request->json('name');    // single value, or null
```

### **Form & Multipart Bodies (PUT / PATCH)**

PHP only populates `$_POST` for POST requests. For `PUT`/`PATCH` with a form payload, parse the raw body manually:

```php
<?php

// application/x-www-form-urlencoded
$form = $request->form();          // all fields
$name = $request->form('name');    // single field

// multipart/form-data
$multipart = $request->multipart();        // all fields
$field     = $request->multipart('field'); // single field
```

### **All Input, Merged**

`all()` merges every available source into one array, routing body parsing by the request's `Content-Type` (JSON, URL-encoded, or multipart). Query and native POST params are always included.

```php
<?php

$input = $request->all(); // $_GET + $_POST + parsed body
```

!!! tip "Which method should I use?"
    Use `all()` when you don't care where a value came from. Use the source-specific methods (`get()`, `post()`, `json()`, `form()`, `multipart()`) when the source matters for correctness or security.

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `get(?string $key)` | `mixed` | GET param by key, or all GET params |
| `post(?string $key)` | `mixed` | Native `$_POST` param by key, or all |
| `json(?string $name)` | `mixed` | Decoded JSON body, a key, or `null` |
| `form(?string $key)` | `mixed` | Parsed `x-www-form-urlencoded` body (any method) |
| `multipart(?string $key)` | `mixed` | Parsed `multipart/form-data` body (any method) |
| `all()` | `array` | All input merged, routed by `Content-Type` |
| `cookie(?string $key)` | `mixed` | Cookie by key, or all cookies |
| `session(?string $key)` | `mixed` | Session value by key, or all (auto-starts session) |

---

## URL Parameters & Query String

### **Route Parameters**

`urlParams()` returns parameters captured from the matched route pattern (e.g. `/users/{id}`).

```php
<?php

// Route: /users/{id}
$all = $request->urlParams();        // ['id' => '42']
$id  = $request->urlParams('id');    // '42', or null if missing
```

### **Query String**

`urlQuery()` parses the URL's query string into an object. Returns the full object, or a single parameter's value by name.

```php
<?php

// URL: /search?term=php&page=2
$query = $request->urlQuery();         // stdClass { term: 'php', page: '2' }
$term  = $request->urlQuery('term');   // 'php', or null if missing
```

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `urlParams(?string $name)` | `mixed` | Matched route parameter by name, or all |
| `urlQuery(?string $name)` | `mixed` | Query string as object, or a single value |

---

## Headers

`header()` returns all request headers, or a single header by name. Keys are normalized to lowercase by default for case-insensitive access; pass `$lowercase = false` to keep original casing (an exact match is then required).

```php
<?php

// All headers (lowercase keys)
$headers = $request->header();

// Specific header (case-insensitive)
$lang = $request->header('Accept-Language'); // 'en-US,en;q=0.9'

// Preserve original casing
$headers = $request->header(null, false);     // ['Accept-Language' => ...]
$lang    = $request->header('Accept-Language', false);
```

### **Common Header Shortcuts**

```php
<?php

$type   = $request->contentType();    // Content-Type header
$length = $request->contentLength();  // Content-Length as int, or null
$token  = $request->csrf();           // X-Csrf-Token header
$xhr    = $request->requestedWith();  // X-Requested-With header
```

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `header(?string $name, bool $lowercase = true)` | `mixed` | All headers, or a single header value |
| `contentType()` | `?string` | `Content-Type` header value |
| `contentLength()` | `?int` | `Content-Length` as int, or `null` |
| `csrf()` | `?string` | `X-Csrf-Token` header value |
| `requestedWith()` | `?string` | `X-Requested-With` header value |

---

## Authentication

### **API Key**

`apiKey()` reads an API key from a request header (default header name `Api-Key`).

```php
<?php

$key = $request->apiKey();            // from the 'Api-Key' header
$key = $request->apiKey('X-App-Key'); // custom header name
```

### **Basic & Bearer Credentials**

`auth()` returns an object exposing both HTTP Basic credentials and a Bearer token, whichever the client sent.

```php
<?php

$auth = $request->auth();

$auth->basic;   // ['username' => ..., 'password' => ...] or null
$auth->bearer;  // 'token-value' or null
```

!!! example "Guarding a route with a Bearer token"

    ```php
    <?php

    use PhpSPA\Http\Request;

    function ProtectedApi(Request $request) {
        $token = $request->auth()->bearer;

        if (!$token || !isValid($token)) {
            http_response_code(401);
            return '{"error":"Unauthorized"}';
        }

        // ... authorized ...
    }
    ```

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `apiKey(string $key = 'Api-Key')` | `mixed` | API key from the given header |
| `auth()` | `stdClass` | `{ basic, bearer }` credentials object |

---

## URLs & Origin

```php
<?php

$request->path();      // '/users/42'            (path only, decoded)
$request->getUri();    // '/users/42'            (URI path, decoded)
$request->baseURL();   // 'https://example.com'  (scheme + host)
$request->siteURL();   // 'https://example.com/users/42'
$request->origin();    // 'https://example.com'  (from Origin/Referer)
```

### **Same-Origin Checking**

`isSameOrigin()` compares the request's `Origin` (or `Referer`) against the server host — useful for CSRF-style validation.

```php
<?php

if (!$request->isSameOrigin()) {
    http_response_code(403);
    return 'Cross-origin request rejected';
}
```

### **API Reference**

| Method | Returns | Description |
| :--- | :--- | :--- |
| `path()` | `string` | Request path, query string stripped |
| `baseURL()` | `string` | Scheme + host (e.g. `https://example.com`) |
| `siteURL()` | `string` | Full URL (base + path) |
| `origin()` | `string` | Request origin from `Origin`/`Referer` |
| `isSameOrigin()` | `bool` | Whether the request is same-origin |

---

## Dynamic Attributes

The Request object supports attaching arbitrary data at runtime via magic property access — handy for passing values from middleware down to a component.

```php
<?php

// In middleware
$request->currentUser = $user;

// Later, in a component
$user = $request->currentUser; // null if never set
```

| Method | Returns | Description |
| :--- | :--- | :--- |
| `__set($name, $value)` | `void` | Store a dynamic attribute |
| `__get($name)` | `mixed` | Read a dynamic attribute, or `null` |
