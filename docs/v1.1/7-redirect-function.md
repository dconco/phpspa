# Redirect Function

## Overview

```php
function Redirect(string $url, int $code = 0): never
```

Immediately terminates execution and redirects to specified URL with HTTP status code.

## Basic Usage

```php
use function phpSPA\Http\Redirect;

// Simple redirect
Redirect('/dashboard');

// Permanent redirect
Redirect('/new-location', 301);
```

## Parameters

| Parameter | Type   | Default | Description                             |
| --------- | ------ | ------- | --------------------------------------- |
| `$url`    | string | -       | Absolute or relative URL to redirect to |
| `$code`   | int    | `0`     | HTTP status code (defaults to 302)      |

## Supported HTTP Codes

| Code  | Type      | Header Sent                       |
| ----- | --------- | --------------------------------- |
| `0`   | Automatic | `Location: $url` (302 equivalent) |
| `301` | Permanent | `HTTP/1.1 301 Moved Permanently`  |
| `302` | Temporary | `HTTP/1.1 302 Found`              |
| `303` | See Other | `HTTP/1.1 303 See Other`          |
| `307` | Temporary | `HTTP/1.1 307 Temporary Redirect` |
| `308` | Permanent | `HTTP/1.1 308 Permanent Redirect` |

## Advanced Usage

### With Query Parameters

```php
Redirect('/search?q=' . urlencode($query));
```

### Conditional Redirect

```php
if ($user->isGuest()) {
    Redirect('/login', 303);
}
```

### Framework Integration

```php
try {
    // Some operation
} catch (AuthException $e) {
    Redirect('/error?code=auth_failed', 307);
    // Script terminates here
}
// This line won't execute
```

## Security Considerations

1. **URL Validation**

   ```php
   $safeUrl = filter_var($inputUrl, FILTER_VALIDATE_URL);
   Redirect($safeUrl ?: '/default');
   ```

2. **Header Injection Protection**

   ```php
   // Automatically handled by function
   Redirect(htmlspecialchars($userInputPath));
   ```

## Best Practices

1. **Use Early**

   ```php
   // At top of controller:
   if (!user_authenticated()) {
       Redirect('/login');
   }
   ```

2. **Code Selection**
   - `301` for permanent URL changes
   - `302/307` for temporary redirects
   - `303` for POST-redirect-GET pattern

3. **Termination**

   ```php
   Redirect('/exit');
   cleanup(); // This will never execute
   ```

## Troubleshooting

| Issue                | Solution                 |
| -------------------- | ------------------------ |
| Headers already sent | Call before any output   |
| Infinite loops       | Verify redirect target   |
| Invalid URL          | Use `filter_var()` first |

## Next

- [Navigate Component](./8-navigate-component.md)
