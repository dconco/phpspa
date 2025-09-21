# phpSPA v1.1.8 — Release Notes

## Summary

Introduces server-side routing and response ergonomics: automatic dispatch during the request lifecycle with first-class pattern/typed-parameter matching and a concise, fluent Response API for building JSON and HTTP responses.

## Highlights

- Response (new helpers): `Response::json`, `Response::make`, `Response::sendJson`, `Response::sendSuccess`, `Response::sendError` and the `response()` helper for fluent construction of JSON and HTTP responses.
- Pattern support: MapRoute enables typed params and richer patterns — validation and conversion happen during matching.
- Fluent callbacks: route handlers return a `Response` (or use `response()`), enabling chained calls like `response()->json(...)->header(...)->contentType(...)`.

## Quick example

```php
use phpSPA\Http\Router;
use phpSPA\Http\Response;

Router::get('/user/{id: int}', function($req, int $id) {
  return Response::json(['user_id' => $id]);
});
```

## Docs

- Full developer documentation: `https://phpspa.readthedocs.io/en/latest/v1.1.8`
- Changelog entry: `CHANGELOG.md`

## Author / Credits

- Feature author: [Samuel Pascal](https://github.com/SamuelPaschalson) — implemented this release feature: Router & Response
- Maintainer: [Dave Conco](https://github.com/dconco)