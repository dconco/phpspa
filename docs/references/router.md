# Router & Middleware API Reference

!!! abstract "New in v2.0.4"
    The enhanced routing and middleware features described in this document were introduced in **PhpSPA v2.0.4**.

The Router system in PhpSPA provides a powerful way to manage application routes, groups, and middleware. It supports nested prefixes, group-level middleware, and specific route middleware.

!!! info "Namespace"
    `PhpSPA\Http\Router`

---

## **Route Grouping**

Grouping routes allows you to share common URI prefixes and middleware across a set of routes.

### **App Level Prefixing**

Use the `App::prefix` method to define a top-level group in your application entry point.

```php
<?php

$app->prefix('/api', function (Router $router) {
    // Routes defined here will be prefixed with /api
    $router->get('/users', function ($req, $res) {
        return $res->success(['user1', 'user2']);
    });
});
```

| Method | Description |
| :--- | :--- |
| `prefix(string $path, callable $handler)` | Registers a route group behavior. The `$handler` receives a `Router` instance. |

### **Nested Prefixes**

Inside a route group, you can further nest routes using `$router->prefix`.

```php
<?php

$app->prefix('/api', function (Router $router) {
    
    // Creates /api/v1
    $router->prefix('/v1', function (Router $router) {
        
        // Matches GET /api/v1/status
        $router->get('/status', fn($req, $res) => $res->success('API V1 Online'));
        
    });

});
```

---

## **Middleware**

Middleware provide a convenient mechanism for inspecting and filtering HTTP requests entering your application.

!!! note "Looking for Component/App middleware?"
    This page documents **Router middleware** which receives `(Request, Response, Closure $next)`.

    For `App->middleware()` / `Component->middleware()` (which receives `(Request, Closure $next)`), see: [App & Component Middleware](middleware.md).

### **Middleware Signature**

Middleware closures receive three arguments:

1. `Request $request`: The HTTP request object.
2. `Response $response`: The Response factory.
3. `Closure $next`: The callback to pass control to the next middleware.

```php
<?php

function (Request $req, Response $res, Closure $next) {
    if (!valid()) {
        return $res->unauthorized('Access Denied');
    }
    return $next();
};
```

### **Group Middleware**

You can apply middleware to a whole group of routes using `$router->middleware()`. This middleware runs for every route defined within the group (and sub-groups).

```php
<?php

$app->prefix('/admin', function (Router $router) {
    
    // Apply authentication middleware to all /admin routes
    $router->middleware(function ($req, $res, $next) {
        if (!$req->session('user_id')) {
            return $res->redirect('/login');
        }
        return $next();
    });

    $router->get('/dashboard', ...);
    $router->get('/settings', ...);

});
```

### **Route Specific Middleware**

You can assign middleware to specific routes by passing additional callables before the final handler.

```php
<?php

$checkRole = function ($req, $res, $next) {
    // Custom logic
    return $next();
};

$router->get('/users/{id}', $checkRole, function ($req, $res) {
    return $res->success('User Data');
});
```

---

## **Static Files**

Serve static files directly without manual route definitions using `App::useStatic`.

```php
<?php

// Maps http://example.com/assets -> /var/www/public/assets
$app->useStatic('/assets', __DIR__ . '/../public/assets');
```

| Method | Description |
| :--- | :--- |
| `useStatic(string $route, string $path)` | Maps a URL route to a filesystem directory or file. |

---

## **API Methods**

The `Router` instance supports standard HTTP verbs.

| Method | Description |
| :--- | :--- |
| `get($route, ...$handlers)` | Register a GET route |
| `post($route, ...$handlers)` | Register a POST route |
| `put($route, ...$handlers)` | Register a PUT route |
| `patch($route, ...$handlers)` | Register a PATCH route |
| `delete($route, ...$handlers)` | Register a DELETE route |
| `head($route, ...$handlers)` | Register a HEAD route |

**Example:**
```php
<?php

$router->post('/users', $validatorMiddleware, function ($req, $res) {
    return $res->created(null, 'User created');
});
```
