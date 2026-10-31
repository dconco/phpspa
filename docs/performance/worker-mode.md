# Worker Mode (FrankenPHP, RoadRunner)

PhpSPA supports persistent worker mode runtimes like FrankenPHP and RoadRunner, where the PHP process stays alive across requests instead of terminating after each one.

## Enabling Worker Mode

Pass `true` to `App->run()` to return output instead of echoing and exiting:

```php
$output = $app->run(true);
```

## Worker Loop Example

```php
<?php

use const PhpSPA\Http\PREFLIGHT_HANDLED;

// FrankenPHP worker loop
while ($request = \frankenphp_handle_request()) {
    // Reset any static state between requests
    // (PhpSPA handles its own internal resets)
    
    $app = new App();
    // ... configure your app ...
    
    $output = $app->run(true);
    
    // Handle CORS preflight (OPTIONS) requests
    if ($output === PREFLIGHT_HANDLED) {
        // Headers already sent, no body needed
        continue;
    }
    
    // Output the response
    if (isset($output)) {
        echo $output;
    }
}
```

## Return Values

When using `run(true)`, the method returns different values depending on the request type:

| Request Type | Return Value | Notes |
|--------------|--------------|-------|
| HTML page | `string` | Compressed HTML output |
| PHPSPA (SPA navigation) | `string` | JSON response |
| Asset (CSS/JS) | `string` | Asset content |
| Expired asset | `string` | `"Asset has expired"` with 410 status |
| CORS preflight (OPTIONS) | `PREFLIGHT_HANDLED` | Constant, headers already sent |
| No matching route | `null` | No output |

## The `PREFLIGHT_HANDLED` Constant

Import the constant to check for handled preflight requests:

```php
use const PhpSPA\Http\PREFLIGHT_HANDLED;

$output = $app->run(true);

if ($output === PREFLIGHT_HANDLED) {
    // CORS headers sent, 204 status set, no body needed
    return;
}
```

The constant value is `'__phpspa_preflight_handled__'`, but always use the constant for forward compatibility.

## Static State Reset

PhpSPA automatically resets internal static state between requests:

- DOM utilities (`DOM::reset()`)
- Nonce generation (`Nonce::reset()`)
- Session handlers
- Component scope

Your application code should also reset any static/global state it maintains between requests.

## Redirects and Early Exits

**Note:** User-level APIs like `Response->redirect()`, `Redirect()`, and `Response->send()` still call `exit()`. In worker mode, use these patterns instead:

```php
// Instead of:
$response->redirect('/dashboard');

// Use:
header('Location: /dashboard', true, 302);
return; // In your worker loop
```

A future version may add worker-compatible redirect helpers.
