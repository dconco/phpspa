<?php

namespace PhpSPA\Http;

use Closure;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Core\Router\MapRoute;
use PhpSPA\Core\Router\PrefixRouter;
use PhpSPA\Http\Response;

/**
 * Handles routing for the application.
 * 
 * @package HTTP
 * @author Samuel Paschalson <samuelpaschalson@gmail.com>
 * @copyright 2026 Samuel Paschalson
 * @method void get(string|array $route, callable ...$handlers) Register a GET route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void put(string|array $route, callable ...$handlers) Register a PUT route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void post(string|array $route, callable ...$handlers) Register a POST route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void head(string|array $route, callable ...$handlers) Register a HEAD route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void patch(string|array $route, callable ...$handlers) Register a PATCH route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void delete(string|array $route, callable ...$handlers) Register a DELETE route. Handlers receive (Request $request, Response $response, Closure $next).
 * @see https://phpspa.tech/references/response/#router-quick-examples
 */

class Router
{
   use PrefixRouter;

   /**
    * @param bool $caseSensitive Whether routes are case sensitive
    * @param string $prefix Base path for the router
    * @param array<callable> $middlewares
    */
   public function __construct(readonly private string $prefix, private bool $caseSensitive, private array $middlewares)
   {
      if (!isset(static::$request_uri)) {
         $request = new HttpRequest();
         static::$request_uri = $request->getUri();
      }
   }

   /**
    * Set whether routes are case sensitive.
    */
   public function caseSensitive(bool $value): void
   {
      $this->caseSensitive = $value;
   }

   /**
    * Adds a middleware to the router group.
    * 
    * @param callable $handler The middleware handler.
    * @return void
    * @since v2.0.4
    * @see https://phpspa.tech/references/router/#middleware
    */
   public function middleware(callable $handler) {
      $this->middlewares[] = $handler;
   }

   /**
    * Creates a route group with a specific prefix.
    * 
    * @param string $path The URL prefix for this group.
    * @param callable $handler The callback to define routes within this group.
    * @return void
    * @since v2.0.4
    * @see https://phpspa.tech/references/router/#nested-prefixes
    */
   public function prefix(string $path, callable $handler) {
      $prefix = ['path' => rtrim($this->prefix, '/') . '/' . ltrim($path, '/'), 'handler' => $handler];
      $this->handlePrefix($prefix, $this->middlewares);
   }

   public function __call($method, $args)
   {
      $routes = !\is_array($args[0]) ? [$args[0]] : [$args[0]];
      unset($args[0]);

      $handlers = [...$this->middlewares, ...$args];
      $routes = array_map(fn($route) => rtrim($this->prefix, '/') . '/' . ltrim($route, '/'), $routes);

      match ($method) {
         'get',
         'put',
         'post',
         'head',
         'patch',
         'delete' => $this->handle($method, $routes, ...$handlers),
         default => throw new \BadMethodCallException("Method {$method} does not exist in " . __CLASS__),
      };
   }
   
   private function handleHandler(callable $handler, &$request, $response, Closure $next) {
      return \call_user_func($handler, $request, $response, $next);
   }

   private function handle(string $method, array $route, callable ...$handlers): void
   {
      $response = new Response();
      $iterator = 0;

      $mapRoute = new MapRoute($method, $route, $this->caseSensitive);
      $map = $mapRoute->match();

      if ($map) {
         $request = new HttpRequest($map['params'] ?? []);

         $next = function() use (&$iterator, $handlers, &$request, $response, &$next) {
            if (++$iterator >= \count($handlers)) return;
            return $this->handleHandler($handlers[$iterator], $request, $response, $next);
         };

         $output = $this->handleHandler($handlers[$iterator], $request, $response, $next);

			if ($output) {
            if ($output instanceof Response) {
               $output->send();
            }

				echo $output;
				exit;
			}
      }
   }
}
