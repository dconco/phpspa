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
   protected function handlePrefix(array $prefix, array $middlewares = [], bool $return = false) {
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
            return: $return
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

   protected function resolveStaticPath(bool $return) {
      $request_uri = trim(static::$request_uri, '/');

      foreach ($this->static as $static) {
         $route = trim($static['route'], '/');
         $path = substr($request_uri, 0, \strlen($route));

         if ($path === $route) {
            $staticRoute = substr($request_uri, \strlen($route));
            $filePath = rtrim($static['staticPath'], '/') . '/' . ltrim($staticRoute, '/');
            
            if (is_file($filePath)) {
               if ($return) {
                  return (new Response())->sendFile($filePath)->__toString();
               }

               (new Response())->sendFile($filePath)->send();
            }
         }
      }
   }
}
