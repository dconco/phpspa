# Performance: Worker Mode

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

PhpSPA supports persistent worker mode runtimes like **FrankenPHP**, **RoadRunner**, and **Swoole**, where the PHP process stays alive across requests instead of terminating after each one. ⚡

!!! info "Why Worker Mode?"
    Traditional PHP terminates after every request, losing all in-memory state. Worker mode keeps the process alive, eliminating cold-start overhead and enabling faster response times.

## Enabling Worker Mode

Pass `true` to `App->run()` to return output instead of echoing and exiting:

```php
<?php
use PhpSPA\App;

$app = new App($layout);
// ... configure your app ...

// Returns output instead of echo + exit
$output = $app->run(true);
```

!!! success "No `exit()` Calls"
    When using `run(true)`, PhpSPA returns control to your worker loop instead of calling `exit()`. This keeps your worker process alive for the next request.

## Worker Loop Example

=== "FrankenPHP"

    ```php
    <?php
    
    use PhpSPA\App;
    
    // FrankenPHP worker loop
    while ($request = \frankenphp_handle_request()) {
        // Create fresh App instance per request
        $app = new App($layout);
        $app->attach($homeComponent);
        $app->attach($dashboardComponent);
        
        $output = $app->run(true);
        
        // Handle CORS preflight (OPTIONS) requests
        if ($output === PREFLIGHT_HANDLED) {
            // Headers already sent (204), no body needed
            continue;
        }
        
        // Output the response
        if (isset($output)) {
            echo $output;
        }
    }
    ```

=== "RoadRunner"

    ```php
    <?php
    
    use PhpSPA\App;
    use Spiral\RoadRunner\Http\PSR7Worker;
    
    $worker = new PSR7Worker(...);
    
    while ($request = $worker->waitRequest()) {
        $app = new App($layout);
        // ... configure ...
        
        $output = $app->run(true);
        
        if ($output === PREFLIGHT_HANDLED) {
            $worker->respond(new Response(204));
            continue;
        }
        
        if (isset($output)) {
            $worker->respond(new Response(200, [], $output));
        }
    }
    ```

=== "Swoole"

    ```php
    <?php
    
    use PhpSPA\App;
    
    $server = new Swoole\HTTP\Server('0.0.0.0', 9501);
    
    $server->on('request', function ($request, $response) use ($layout) {
        $app = new App($layout);
        // ... configure ...
        
        $output = $app->run(true);
        
        if ($output === PREFLIGHT_HANDLED) {
            $response->status(204);
            $response->end();
            return;
        }
        
        if (isset($output)) {
            $response->end($output);
        }
    });
    
    $server->start();
    ```

## Return Values

When using `run(true)`, the method returns different values depending on the request type:

| Request Type | Return Value | HTTP Status | Notes |
|--------------|--------------|-------------|-------|
| HTML page | `string` | 200 | Compressed HTML output |
| PHPSPA navigation | `string` | 200 | JSON response for SPA |
| Asset (CSS/JS) | `string` | 200 | Asset content |
| Expired asset | `string` | 410 | `"Asset has expired"` |
| CORS preflight | `PREFLIGHT_HANDLED` | 204 | Constant, headers sent |
| No matching route | `null` | — | No output |

!!! tip "Checking Return Types"
    ```php
    <?php

    $output = $app->run(true);
    
    if ($output === PREFLIGHT_HANDLED) {
        // OPTIONS request handled, 204 status set
        return;
    }
    
    if ($output === null) {
        // No route matched
        http_response_code(404);
        echo "Not Found";
        return;
    }
    
    // Normal response
    echo $output;
    ```

## The `PREFLIGHT_HANDLED` Constant

Import the constant to check for handled CORS preflight (OPTIONS) requests:

```php
<?php

$output = $app->run(true);

if ($output === PREFLIGHT_HANDLED) {
    // CORS headers sent, 204 status set, no body needed
    return;
}
```

!!! info "Constant Value"
    The constant value is `'__phpspa_preflight_handled__'`, but always use the constant for forward compatibility.

## Static State Reset

PhpSPA automatically resets internal static state between requests when using `run(true)`:

- [x] DOM utilities (`DOM::reset()`)
- [x] Nonce generation (`Nonce::reset()`)
- [x] Session handlers
- [x] Component scope

!!! warning "Your Application State"
    Your own application code should also reset any static/global state it maintains between requests. Worker mode keeps the process alive, so static variables persist across requests.

    ```php
    <?php
    // Bad - state leaks between requests
    class Counter {
        public static int $count = 0;
    }
    
    // Good - reset in worker loop
    while ($request = \frankenphp_handle_request()) {
        Counter::$count = 0; // Reset before each request
        // ...
    }
    ```

## Redirects and Early Exits

!!! warning "User-Level APIs Still Exit"
    User-level redirect APIs like `Response->redirect()`, `Redirect()`, and `Response->send()` still call `exit()`. In worker mode, use header-based redirects instead:

=== "Don't use (exits)"

    ```php
    <?php
    // This will kill your worker process!
    $response->redirect('/dashboard');
    ```

=== "Use instead (returns)"

    ```php
    <?php
    // Set headers and return control to your loop
    header('Location: /dashboard', true, 302);
    return; // In your worker loop
    ```

!!! tip "Future Enhancement"
    A future version may add worker-compatible redirect helpers that return instead of exiting.

## Performance Benefits

Worker mode provides significant performance improvements:

| Metric | Traditional PHP | Worker Mode |
|--------|-----------------|-------------|
| Process startup | Every request | Once |
| Autoloader | Every request | Once |
| Framework bootstrap | Every request | Once |
| Memory allocation | Every request | Reused |
| OPcache | Warm after first | Always warm |

!!! success "Typical Improvements"
    - **2-10x faster** response times for simple pages
    - **Lower memory** usage per request (shared process)
    - **Better CPU** utilization (no fork overhead)

## Complete Example

```php
<?php

use PhpSPA\App;
use PhpSPA\Component;
use PhpSPA\Compression\Compressor;

// Layout defined once (persists across requests)
$layout = fn() => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    {{ content }}
</body>
</html>
HTML;

// FrankenPHP worker loop
while ($request = \frankenphp_handle_request()) {
    // Fresh App instance per request
    $app = new App($layout);
    
    // Configure compression
    $app->compression(Compressor::LEVEL_EXTREME, true);
    
    // Attach components
    $app->attach(
        (new Component(fn() => '<h1>Home</h1>'))
            ->route(['/'])
            ->title('Home')
    );
    
    $app->attach(
        (new Component(fn() => '<h1>About</h1>'))
            ->route(['/about'])
            ->title('About')
    );
    
    // Run and get output
    $output = $app->run(true);
    
    // Handle preflight
    if ($output === PREFLIGHT_HANDLED) {
        continue;
    }
    
    // Output response
    if (isset($output)) {
        echo $output;
    }
}
```

!!! tip "Method Chaining"
    All configuration methods return `$this`, so you can chain them:

    ```php
    <?php
    $output = (new App($layout))
        ->compression(Compressor::LEVEL_EXTREME, true)
        ->attach($homeComponent)
        ->attach($aboutComponent)
        ->run(true);
    ```
