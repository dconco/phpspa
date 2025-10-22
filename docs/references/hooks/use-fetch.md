# `useFetch` API

The `useFetch` hook provides a fluent interface for making HTTP requests.

## 1. Simple GET

To perform a simple `GET` request, just use `useFetch` where you'd use a string or array. It will automatically make the request and return the body.

```php
// Use 'echo' to get the raw text body
echo useFetch('https://api.example.com/users/1');

// Use as an array to get the decoded JSON
$user = useFetch('https://api.example.com/users/1');
echo $user['data']['first_name'];

// Use as an object to get the decoded JSON
$user = useFetch('https://api.example.com/users/1');
echo $user->data['first_name'];
```

-----

## 2. Method Chaining

For all other requests, you must chain the HTTP method. The method call (`->get()`, `->post()`, etc.) is always the *last* step and returns a `ClientResponse` object.

**`->get(array $query = null)`**
Sends query parameters.

```php
$response = useFetch('https://api.example.com/users')
               ->get(['page' => 2]);
```

**`->post(array $body = null)`**
Sends a JSON request body.

```php
$newUser = [
   'name' => 'Dave Conco',
   'job' => 'Developer'
];
$response = useFetch('https://api.example.com/users')
               ->post($newUser);
```

**`->put(array $body = null)`**
Sends a JSON request body.

```php
$updatedUser = [
   'name' => 'Dave Conco',
   'job' => 'Senior Developer'
];
$response = useFetch('https://api.example.com/users/2')
               ->put($updatedUser);
```

**`->patch(array $body = null)`**
Sends a JSON request body.

```php
$update = ['job' => 'Lead Developer'];
$response = useFetch('https://api.example.com/users/2')
               ->patch($update);
```

**`->delete(array $query = null)`**
Sends query parameters (no body).

```php
$response = useFetch('https://api.example.com/users/2')
               ->delete(['force' => 'true']);
```

-----

## 3. Adding Headers

Use the `->headers()` method *before* the HTTP method.

```php
$response = useFetch('https://api.example.com/user')
               ->headers([
                  'Authorization' => 'Bearer your_token_here',
                  'X-Custom-Header' => 'MyValue'
               ])
               ->get();
```

-----

## 4. Request Configuration

Configure request behavior using fluent methods *before* the HTTP method.

### Timeout Configuration

**`->timeout(int $seconds)`**
Set request timeout in seconds. Supports decimals for sub-second timeouts.

```php
// 30 second timeout
$response = useFetch('https://api.example.com/users')
               ->timeout(30)
               ->get();

// 500ms timeout (0.5 seconds)
$response = useFetch('https://api.example.com/users')
               ->timeout(0.5)
               ->get();
```

**`->connectTimeout(int $seconds)`**
Set connection timeout in seconds. Only available when cURL is enabled.

```php
$response = useFetch('https://api.example.com/users')
               ->connectTimeout(5)
               ->timeout(30)
               ->get();
```

### SSL/TLS Configuration

**`->verifySSL(bool $verify = true)`**
Enable or disable SSL certificate verification.

```php
// Disable SSL verification (not recommended for production)
$response = useFetch('https://self-signed.example.com/api')
               ->verifySSL(false)
               ->get();
```

**`->withCertificate(string $path)`**
Set path to CA certificate bundle for SSL verification.

```php
$response = useFetch('https://api.example.com/users')
               ->withCertificate('/path/to/cacert.pem')
               ->get();
```

### Redirect Configuration

**`->followRedirects(bool $follow = true, int $maxRedirects = 10)`**
Enable or disable following redirects. Only available when cURL is enabled.

```php
// Follow up to 5 redirects
$response = useFetch('https://api.example.com/redirect')
               ->followRedirects(true, 5)
               ->get();

// Disable redirects
$response = useFetch('https://api.example.com/redirect')
               ->followRedirects(false)
               ->get();
```

### User Agent

**`->withUserAgent(string $userAgent)`**
Set a custom User-Agent header.

```php
$response = useFetch('https://api.example.com/users')
               ->withUserAgent('MyApp/1.0')
               ->get();
```

### Custom Options

**`->withOptions(array $options)`**
Set custom options directly. Advanced usage.

```php
$response = useFetch('https://api.example.com/users')
               ->withOptions([
                  'timeout' => 60,
                  'verify_ssl' => false,
                  'user_agent' => 'CustomAgent/2.0'
               ])
               ->get();
```

-----

## 5. Handling the Response

All chained methods return a `ClientResponse` object.

```php
$response = useFetch('https://api.example.com/users/2')->get();

// Get the decoded JSON as an array
$data = $response->json(); // e.g., ['data' => [...]]

// Get the raw response body as a string
$text = $response->text(); // e.g., '{"data":{...}}'

// Get the HTTP status code
$status = $response->status(); // e.g., 200

// Get an associative array of response headers
$headers = $response->headers(); // e.g., ['Content-Type' => 'application/json']

// Check if the request was successful (status 200-299)
if ($response->ok()) {
   // ... success
} else {
   // ... handle error
}

// Check if the request failed
if ($response->failed()) {
   echo 'Request failed: ' . $response->error();
}

// Get error message if request failed
$error = $response->error(); // Returns null if no error, string if failed
```

-----

## 6. Complete Examples

### POST with Configuration

```php
$data = [
   'name' => 'Dave Conco',
   'email' => 'dave@example.com'
];

$response = useFetch('https://api.example.com/users')
               ->headers(['Authorization' => 'Bearer token123'])
               ->timeout(15)
               ->verifySSL(true)
               ->withUserAgent('MyApp/1.0')
               ->post($data);

if ($response->ok()) {
   $result = $response->json();
   echo "User created with ID: " . $result['id'];
} else {
   echo "Error: " . $response->error();
}
```

### GET with Error Handling

```php
$response = useFetch('https://api.example.com/users/123')
               ->timeout(10)
               ->get();

if ($response->failed()) {
   // Handle error
   error_log('API Error: ' . $response->error());
   echo "Failed to fetch user";
} else {
   // Success
   $user = $response->json();
   echo "User: " . $user['name'];
}
```

### Custom Configuration

```php
$response = useFetch('https://slow-api.example.com/data')
               ->headers([
                  'Authorization' => 'Bearer token',
                  'X-Custom-Header' => 'value'
               ])
               ->timeout(60)
               ->connectTimeout(10)
               ->followRedirects(true, 3)
               ->withUserAgent('MyApp/2.0')
               ->verifySSL(true)
               ->get(['page' => 1, 'limit' => 50]);

if ($response->ok()) {
   $data = $response->json();
   echo "Received " . count($data['items']) . " items";
} else {
   echo "Request failed: " . $response->error();
   echo "Status code: " . $response->status();
}
```

-----

### Avoid Same-Server Requests

```php
// ❌ Don't do this - creates deadlock
$response = useFetch('http://localhost:8000/same-app-route')->get();

// ✅ Instead, call the function directly
$data = getSomeData();
```