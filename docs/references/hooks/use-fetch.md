# `useFetch` API

The `useFetch` hook provides a fluent interface for making HTTP requests.

## 1. Basic Usage

### Simple GET Request

```php
// Direct usage - returns decoded JSON
echo useFetch('https://api.example.com/users/1');

// Array access
$user = useFetch('https://api.example.com/users/1');
echo $user['data']['first_name'];

// With query parameters
$response = useFetch('https://api.example.com/users')->get(['page' => 2]);
```

### Other HTTP Methods

```php
// POST - sends JSON body
$response = useFetch('https://api.example.com/users')
    ->post(['name' => 'Dave', 'job' => 'Developer']);

// PUT - update resource
$response = useFetch('https://api.example.com/users/2')
    ->put(['job' => 'Senior Developer']);

// PATCH - partial update
$response = useFetch('https://api.example.com/users/2')
    ->patch(['job' => 'Lead Developer']);

// DELETE - with query params
$response = useFetch('https://api.example.com/users/2')
    ->delete(['force' => 'true']);
```

-----

## 2. Configuration

Chain configuration methods before the HTTP method:

```php
$response = useFetch('https://api.example.com/users')
    ->headers(['Authorization' => 'Bearer token'])
    ->timeout(30)              // Request timeout (seconds, supports decimals)
    ->connectTimeout(5)        // Connection timeout (cURL only)
    ->verifySSL(true)          // SSL verification
    ->withCertificate('/path/to/cacert.pem')  // Custom CA bundle
    ->followRedirects(true, 5) // Follow up to 5 redirects (cURL only)
    ->withUserAgent('MyApp/1.0')
    ->get();
```

-----

## 3. Response Handling

```php
$response = useFetch('https://api.example.com/users/2')->get();

$data = $response->json();      // Decoded JSON array
$text = $response->text();      // Raw response body
$status = $response->status();  // HTTP status code (200, 404, etc.)
$headers = $response->headers(); // Response headers array

// Status checks
if ($response->ok()) {          // 200-299
    // Success
}

if ($response->failed()) {      // Any error
    echo $response->error();    // Error message
}
```

-----

## 4. Asynchronous Requests

!!! note "Requires cURL"
    Async features require cURL extension.

### Single Async Request

```php
$promise = useFetch('https://api.example.com/users/1')->async()->get();
// Do other work...
$response = $promise->wait();
echo $response->json()['name'];
```

### Parallel Execution (Recommended)

Execute multiple requests simultaneously for better performance:

```php
use PhpSPA\Core\Client\AsyncResponse;

// Prepare requests
$user = useFetch('https://api.example.com/users/1')->async()->get();
$posts = useFetch('https://api.example.com/posts')->async()->get(['userId' => 1]);
$comments = useFetch('https://api.example.com/comments')->async()->get(['userId' => 1]);

// Execute all in parallel
[$userRes, $postsRes, $commentsRes] = AsyncResponse::all([$user, $posts, $comments]);

echo $userRes->json()['name'];
echo count($postsRes->json()) . " posts";
```

### With Callbacks

```php
useFetch('https://api.example.com/users/1')
    ->async()
    ->get()
    ->then(fn($res) => print $res->json()['name'])
    ->wait();
```

-----

## 5. Complete Examples

### POST with Error Handling

```php
$response = useFetch('https://api.example.com/users')
    ->headers(['Authorization' => 'Bearer token'])
    ->timeout(15)
    ->post(['name' => 'Dave', 'email' => 'dave@example.com']);

if ($response->ok()) {
    echo "User created: " . $response->json()['id'];
} else {
    error_log('API Error: ' . $response->error());
}
```

### Parallel API Calls

```php
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

-----

## 6. Important Notes

!!! warning "Avoid Same-Server Requests"
    Don't make HTTP requests to the same server handling the current request - it causes deadlock:
    ```php
    // ❌ Don't do this
    $response = useFetch('http://localhost:8000/same-app-route')->get();
    
    // ✅ Do this instead
    $data = getSomeData();
    ```

!!! info "Async Behavior"
    PHP is synchronous. `async()` prepares cURL handles without executing. Use `AsyncResponse::all()` for true parallel execution with curl_multi. Sequential `wait()` calls execute requests one by one.