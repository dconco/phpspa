# `useFetch` API

!!! success "New in v2.0.1"
    :material-new-box: Async HTTP requests with parallel execution support

<div class="grid cards" markdown>

-   :material-lightning-bolt:{ .lg .middle } __Simple & Powerful__

    ---

    Fluent interface for HTTP requests with full async support

-   :material-cog:{ .lg .middle } __Highly Configurable__

    ---

    Timeouts, SSL, headers, redirects - everything you need

-   :material-speedometer:{ .lg .middle } __Parallel Execution__

    ---

    True concurrent requests with curl_multi for maximum performance

-   :material-check-circle:{ .lg .middle } __Modern PHP API__

    ---

    Familiar syntax with modern PHP features

</div>

!!! info "Namespace"
    ```php
    <?php
    use function Component\useFetch;
    ```

---

## :material-rocket-launch: Basic Usage

### Simple GET Request

=== "Direct Usage"
    ```php
    <?php
    // Auto-executes and returns decoded JSON
    echo useFetch('https://api.example.com/users/1');
    ```

=== "Array Access"
    ```php
    <?php
    $user = useFetch('https://api.example.com/users/1');
    echo $user['data']['first_name'];
    ```

=== "With Parameters"
    ```php
    <?php
    $response = useFetch('https://api.example.com/users')
        ->get(['page' => 2, 'limit' => 10]);
    ```

### HTTP Methods

=== "POST"
    ```php
    <?php
    useFetch('https://api.example.com/users')
        ->post(['name' => 'Dave', 'job' => 'Developer']);
    ```

=== "PUT"
    ```php
    <?php
    useFetch('https://api.example.com/users/2')
        ->put(['job' => 'Senior Developer']);
    ```

=== "PATCH"
    ```php
    <?php
    useFetch('https://api.example.com/users/2')
        ->patch(['job' => 'Lead Developer']);
    ```

=== "DELETE"
    ```php
    <?php
    useFetch('https://api.example.com/users/2')
        ->delete(['force' => 'true']);
    ```

---

## :material-cog: Configuration Options

!!! tip "Chain Before HTTP Method"
    All configuration methods must be called before `->get()`, `->post()`, etc.

<div class="annotate" markdown>

```php
<?php
$response = useFetch('https://api.example.com/users')
    ->headers(['Authorization' => 'Bearer token']) // (1)
    ->timeout(30)              // (2)
    ->connectTimeout(5)        // (3)
    ->verifySSL(true)          // (4)
    ->withCertificate('/path/to/cacert.pem') // (5)
    ->followRedirects(true, 5) // (6)
    ->withUserAgent('MyApp/1.0') // (7)
    ->unixSocket('/var/run/service.sock') // (8)
    ->get();
```

</div>

1.  :material-key: **Headers** - Add custom headers (Authorization, API keys, etc.)
2.  :material-timer: **Timeout** - Request timeout in seconds (supports decimals like `0.5`)
3.  :material-connection: **Connect Timeout** - Connection timeout (cURL only)
4.  :material-shield-lock: **SSL Verification** - Enable/disable certificate verification
5.  :material-certificate: **CA Bundle** - Path to custom certificate bundle
6.  :material-arrow-right-bold: **Redirects** - Follow redirects with max limit (cURL only)
7.  :material-account: **User Agent** - Custom User-Agent string
8.  :material-ethernet: **Unix Socket** - Set the Unix domain socket path to be used for this pending request.
9.  :material-tune: **Custom cURL Options** - Pass raw `CURLOPT_*` options via `->withOptions([CURLOPT_* => ...])` (cURL only)

### Custom cURL Options Examples

```php
<?php

// Pass CURLOPT_* directly (advanced cURL-only options)
$res = useFetch('https://api.example.com/users')
    ->withOptions([
        CURLOPT_PROXY => 'http://127.0.0.1:8080',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    ])
    ->get();
```

```php
<?php

// Alternative form: nest under "curl" / "curl_options" (useful if you also pass PhpSPA options)
$res = useFetch('https://api.example.com/users')
    ->timeout(10)
    ->withOptions([
        'curl' => [
            'CURLOPT_PROXY' => 'http://127.0.0.1:8080',
        ],
    ])
    ->get();
```

---

## :material-code-json: Response Handling

<div class="annotate" markdown>

```php
<?php
$response = useFetch('https://api.example.com/users/2')->get();

$data = $response->json();      // (1)
$text = $response->text();      // (2)
$status = $response->status();  // (3)
$headers = $response->headers(); // (4)

if ($response->ok()) {          // (5)
    // Success handling
}

if ($response->failed()) {      // (6)
    echo $response->error();    // (7)
}
```

</div>

1.  :material-code-braces: Returns decoded JSON as associative array
2.  :material-text: Returns raw response body as string
3.  :material-numeric: Returns HTTP status code (200, 404, etc.)
4.  :material-text-box-outline: Returns response headers as array
5.  :material-check-circle: Checks if status is 200-299
6.  :material-alert-circle: Checks if request failed
7.  :material-message-alert: Returns error message string

---

## :material-flash: Asynchronous Requests

!!! info "Requires cURL Extension"
    Async features only work when cURL is available. Falls back to sync execution otherwise.

### Single Async Request

```php
<?php
$promise = useFetch('https://api.example.com/users/1')->async()->get();

// Do other work here...

$response = $promise->wait();
echo $response->json()['name'];
```

### :material-speedometer: Parallel Execution (Recommended)

!!! success "True Concurrency"
    Execute multiple requests simultaneously using `curl_multi` for maximum performance!

=== "Parallel (Fast)"
    ```php
    <?php
    use PhpSPA\Core\Client\AsyncResponse;

    // Prepare requests
    $user = useFetch('https://api.example.com/users/1')->async()->get();
    $posts = useFetch('https://api.example.com/posts')->async()->get(['userId' => 1]);
    $comments = useFetch('https://api.example.com/comments')->async()->get(['userId' => 1]);

    // Execute all simultaneously
    [$userRes, $postsRes, $commentsRes] = AsyncResponse::all([
        $user, $posts, $comments
    ]);

    echo $userRes->json()['name'];
    echo count($postsRes->json()) . " posts";
    ```

=== "Sequential (Slow)"
    ```php
    <?php
    // Without AsyncResponse::all() - executes one by one
    $user = $userPromise->wait()->json();
    $posts = $postsPromise->wait()->json();
    $comments = $commentsPromise->wait()->json();
    ```

### With Callbacks

```php
<?php
useFetch('https://api.example.com/users/1')
    ->async()
    ->get()
    ->then(fn($res) => print $res->json()['name'])
    ->wait();
```

---

## :material-file-document-multiple: Complete Examples

### :material-send: POST with Error Handling

```php
<?php
$response = useFetch('https://api.example.com/users')
    ->headers(['Authorization' => 'Bearer token'])
    ->timeout(15)
    ->post(['name' => 'Dave', 'email' => 'dave@example.com']);

if ($response->ok()) {
    echo "✅ User created: " . $response->json()['id'];
} else {
    error_log('❌ API Error: ' . $response->error());
}
```

### :material-lightning-bolt: Parallel API Calls

```php
<?php
use PhpSPA\Core\Client\AsyncResponse;

$requests = [
    useFetch('https://api.example.com/users/1')->async()->get(),
    useFetch('https://api.example.com/users/2')->async()->get(),
    useFetch('https://api.example.com/users/3')->async()->get(),
];

$responses = AsyncResponse::all($requests);

foreach ($responses as $res) {
    echo $res->json()['name'] . "\n";
}
```

---

## :material-alert: Important Notes

!!! warning "Avoid Same-Server Requests"
    **Never** make HTTP requests to the same server handling the current request - it causes deadlock!
    
    ```php
    <?php
    // ❌ Don't do this
    $response = useFetch('http://localhost:8000/same-app-route')->get();
    
    // ✅ Do this instead
    $data = getSomeData();
    ```

!!! info "Understanding Async Behavior"
    PHP is synchronous by nature. The `async()` method prepares cURL handles without executing them:
    
    - Use `AsyncResponse::all()` for **true parallel execution** with `curl_multi`
    - Sequential `wait()` calls execute requests **one by one**
    - Parallel execution is **significantly faster** for multiple requests