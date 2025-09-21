# phpSPA v1.1.8 — Router & Response

This document describes how to use the new Router and Response APIs introduced in v1.1.8. It contains full usage examples, API reference, and migration tips.

---

## Router — Quick examples

### Automatic dispatch (recommended for web apps)

Register routes normally; the router dispatches automatically when the PHP script ends.

```php
<?php
use phpSPA\Http\Router;
use phpSPA\Http\Response;
use phpSPA\Http\Request;

// Simple route
Router::get('/', function(Request $req) {
    return Response::json(['message' => 'Hello world']);
});

// Parameterized route (MapRoute typed parameter support)
Router::get('/user/{id: int}', function(Request $req, int $id) {
    // $id is validated and typed by MapRoute
    return Response::json(['user_id' => $id]);
});
```

---

## Response — API & examples

`phpSPA\Http\Response` is designed for concise, fluent response construction.

### Create responses

```php
<?php
use phpSPA\Http\Response;

// Basic JSON response (static)
return Response::json(['status' => 'OK']);

// Fluent construction
return (new Response())
    ->data(['result' => 'value'])
    ->status(200)
    ->header('X-Custom', 'value')
    ->contentType('application/json');

// Convenience shortcut
Response::sendJson(['ok' => true]); // builds and sends immediately
```

### Common helper methods

- `Response::json($data, $status = 200, $headers = [])` — create a JSON response.
- `Response::make($data, $status = 200, $headers = [])` — create a Response instance.
- `Response::sendJson(...)`, `Response::sendSuccess(...)`, `Response::sendError(...)` — build & send shortcuts.
- Instance helpers: `data()`, `status()`, `header()`, `contentType()`, `success()`, `error()`.

### Using the global `response()` helper

The repo provides a global helper function `response()` that returns a `Response` instance. Use it for fluent convenience in route callbacks.

```php
<?php
// Example using response() function
return response(['message' => 'ok'], 200)
    ->header('X-Hello', 'world')
    ->contentType('application/json');

// Example using response() function for registering route
response()->get('/example', function(Request $request) {
    return response()->json(['example' => true]);
});
```

---

## Complete example

```php
<?php
require_once 'path/to/vendor/autoload.php';

use phpSPA\Http\Response;
use phpSPA\Http\Request;
use function phpSPA\Http\response;

$request = new Request();
$response = Response::fromRequest($request)->caseSensitive();

// Define your routes
$response->get('/user/{id: int}', function (Request $request, int $id) {
    $user = 2; // example lookup
    return response(['message' => 'Hello from route with ID: ' . $id, 'data' => $user], 200)
        ->header('X-Route-Header', 'route_value');
});

$response->get('/status', function (Request $request) {
    return response()->json([
        'status' => 'OK',
        'message' => 'Server is running.'
    ]);
});

$response->get('/data', function (Request $request) {
    return response()
        ->json(['data' => 'some data'])
        ->header('X-Custom-Header', 'Value')
        ->contentType('application/json');
});

// Convenience methods
$response->get('/success', function (Request $request) {
    return response()->success(['result' => 'data'], 'Operation successful');
});

$response->get('/error', function (Request $request) {
    return response()->error('Something went wrong', 500);
});
```

---

## MapRoute pattern reference

MapRoute supports parameter patterns and strict types in route declarations. Examples:

- `/user/{id}` — simple parameter
- `/user/{id: int}` — typed parameter (int)
- `/search/{q: string}` — string param

For full details see `https://phpspa.readthedocs.io/en/latest/5-route-patterns-and-param-types.md`.

---

If you want, I can also add API-level doc blocks for each Router and Response method into the code so ReadTheDocs extracts them; tell me if you want that next.
