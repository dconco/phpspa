<?php

namespace PhpSPA\Core\Router;

use PhpSPA\Http\Response;
use PhpSPA\Http\Router;

trait PrefixRouter {
   /**
    * The base URI of the application.
    * This is used to determine the root path for routing and resource loading.
    *
    * @var string
    */
   public static string $request_uri;

   /**
    * Indicates whether the application should treat string comparisons as case sensitive.
    *
    * @var bool Defaults to false, meaning string comparisons are case insensitive by default.
    */
   protected bool $defaultCaseSensitive = false;

   /**
    * @var array<array{
    *    route: string,
    *    staticPath: callable
    * }>
    */
   protected array $static = [];

   /**
    * @param array{
    *   path: string,
    *   handler: callable
    * } $prefix
    */
   protected function handlePrefix (array $prefix, array $middlewares = [], bool $return = false)
   {
      // --- Replacing first and last forward slashes, $request_uri will be empty if req uri is / ---
      $request_uri = trim(static::$request_uri, '/');
      $prefixPath = trim($prefix['path'], '/');

      if (!$this->defaultCaseSensitive) {
         $request_uri = strtolower($request_uri);
         $prefixPath = strtolower($prefixPath);
      }

      $path = substr($request_uri, 0, \strlen($prefixPath));

      if ($path === $prefixPath) {
         $router = new Router(
            prefix: $prefix['path'],
            caseSensitive: $this->defaultCaseSensitive,
            middlewares: $middlewares,
            return: $return,
         );

         if ($return) {
            $result = \call_user_func($prefix['handler'], $router);
            if ($result !== null) {
               return $result;
            }

            if (method_exists($router, 'getMatchedOutput')) {
               return $router->getMatchedOutput();
            }

            return null;
         }
         \call_user_func($prefix['handler'], $router);
      }
   }

   protected function resolveStaticPath (bool $return)
   {
      foreach ($this->static as $static) {
         $safePath = $this->resolveContainedStaticFile(
            static::$request_uri,
            $static['route'],
            $static['staticPath']
         );

         if ($safePath !== null) {
            if ($return) {
               return new Response()->sendFile($safePath)->__toString();
            }

            new Response()->sendFile($safePath)->send();
            return;
         }
      }
   }

   /**
    * Resolve a static file request to a real path, enforcing containment.
    *
    * Returns the canonical (realpath) file location ONLY when the resolved
    * file exists and is contained within the configured static directory.
    * Returns null when the route does not match, the file is missing, or the
    * resolved path escapes the static root via path traversal.
    *
    * The request URI is URL-decoded upstream (rawurldecode in
    * HttpRequest::path()), so a payload like `/assets/%2e%2e/%2e%2e/.env`
    * reaches here as literal `../../` segments. Trimming slashes does NOT
    * strip interior `..` segments, so the path must be canonicalized and
    * verified against the static root. See issue #171.
    *
    * @see https://github.com/dconco/phpspa/issues/171
    */
   protected function resolveContainedStaticFile (
      string $requestUri,
      string $route,
      string $staticPath
   ): ?string {
      $requestUri = trim($requestUri, '/');
      $route = trim($route, '/');

      $prefix = substr($requestUri, 0, \strlen($route));
      if ($prefix !== $route) {
         return null;
      }

      $staticRoute = substr($requestUri, \strlen($route));
      $baseDir = rtrim($staticPath, '/');
      $candidate = $baseDir . '/' . ltrim($staticRoute, '/');

      $realBase = realpath($baseDir);
      $realFile = realpath($candidate);

      if ($realBase === false || $realFile === false || !is_file($realFile)) {
         return null;
      }

      $contained = $realFile === $realBase
         || str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR);

      return $contained ? $realFile : null;
   }
}