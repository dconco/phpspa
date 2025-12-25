# App & Component Middleware API Reference

!!! abstract "New in v2.0.5"
    PhpSPA now supports middleware directly on `App` and `Component`.

This middleware layer is designed for guards, request shaping, and shared values that should be available to later middleware and the final component handler.

!!! tip "Different from Router middleware"
    Router middleware (defined on `Router`) uses `(Request, Response, Closure $next)`.

    App/Component middleware uses `(Request, Closure $next)`.

---

## Middleware Signature

App/Component middleware receives:

1. `PhpSPA\Http\Request $request`: The current request instance
2. `Closure $next`: Calls the next middleware or the final handler

```php
<?php

use Closure;
use PhpSPA\Http\Request;

$middleware = function (Request $request, Closure $next): mixed {
   // Do something before
   $result = $next();

   // Optionally do something after
   return $result;
};
```

---

## Shared Request Instance

All App middleware, Component middleware, and the final handler run using the **same `Request` instance**.

That means you can attach values once and reuse them later:

```php
<?php

use Closure;
use PhpSPA\Http\Request;

$app->middleware(function (Request $request, Closure $next) {
   $request->user = Auth::user();
   return $next();
});

new Component(function (Request $request) {
   $user = $request->user;
   return "<h1>Hello {$user->name}</h1>";
});
```

---

## App Middleware

Use `App->middleware()` to apply middleware globally (it runs for every matching component):

```php
<?php

use Closure;
use PhpSPA\Http\Request;

$app->middleware(function (Request $request, Closure $next) {
   if (!$request->auth()->bearer) {
      http_response_code(403);
      return '<h2>Unauthorized</h2>';
   }

   return $next();
});
```

---

## Component Middleware

Use `Component->middleware()` to guard or shape a single component/route:

```php
<?php

use Closure;
use PhpSPA\Component;
use PhpSPA\Http\Request;

new Component(function (Request $request) {
   return '<h2>Private Area</h2>';
})
   ->route('/private')
   ->middleware(function (Request $request, Closure $next) {
      if (!$request->auth()->bearer) {
         http_response_code(403);
         return '<h2>Unauthorized</h2>';
      }

      return $next();
   });
```
