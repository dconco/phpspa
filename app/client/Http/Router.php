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
 * @author Dave Conco <me@dconco.tech>
 * @copyright 2026 Samuel Paschalson
 * @method void get(string|array $route, callable ...$handlers) Register a GET route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void put(string|array $route, callable ...$handlers) Register a PUT route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void post(string|array $route, callable ...$handlers) Register a POST route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void head(string|array $route, callable ...$handlers) Register a HEAD route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void patch(string|array $route, callable ...$handlers) Register a PATCH route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void delete(string|array $route, callable ...$handlers) Register a DELETE route. Handlers receive (Request $request, Response $response, Closure $next).
 * @method void methods(string|array $methods, string|array $route, callable ...$handlers) Register a route for multiple HTTP methods.
 * @see https://phpspa.tech/references/response/#router-quick-examples
 */

class Router
{
   use PrefixRouter;

   private bool $matched = false;
   private mixed $matchedOutput = null;

   /**
    * @param bool $caseSensitive Whether routes are case sensitive
    * @param string $prefix Base path for the router
    * @param array<callable|string> $middlewares
    */
   public function __construct(readonly private string $prefix, private bool $caseSensitive, private array $middlewares, private bool $return = false)
   {
      static::$request_uri = new HttpRequest()->getUri();
      $this->matched = false;
      $this->matchedOutput = null;
   }

   public function getMatchedOutput(): mixed
   {
      if (!$this->matched) {
         return null;
      }

      return $this->matchedOutput ?? '';
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
    * @param callable|string $handler The middleware handler.
    * @return void
    * @since v2.0.4
    * @see https://phpspa.tech/references/router/#middleware
    */
   public function middleware(callable|string $handler) {
      $this->middlewares[] = $handler;
   }

   /**
    * Creates a route group with a specific prefix.
    * 
    * @param string $path The URL prefix for this group.
    * @param callable|string $handler The callback to define routes within this group.
    * @return void
    * @since v2.0.4
    * @see https://phpspa.tech/references/router/#nested-prefixes
    */
   public function prefix(string $path, callable|string $handler) {
      $prefix = ['path' => rtrim($this->prefix, '/') . '/' . ltrim($path, '/'), 'handler' => $handler];

      if ($this->return) {
         $output = $this->handlePrefix($prefix, $this->middlewares, true);

         if ($output !== null) {
            $this->matched = true;
            $this->matchedOutput = $output;
            return $output;
         }

         return null;
      }

      $this->handlePrefix($prefix, $this->middlewares, false);
   }

   /**
    * Register a route for multiple HTTP methods.
    *
    * @param string|array $methods HTTP methods (e.g., 'GET|POST' or ['GET', 'POST']).
    * @param string|array $route Route pattern(s).
    * @param callable|string ...$handlers Route handlers.
    */
   public function methods(string|array $methods, string|array $route, callable|string ...$handlers)
   {
      $routes = 
         !\is_array($route)
            ? [$route]
            : $route;

      $handlers = [...$this->middlewares, ...$handlers];
      $routes = array_map(fn($route) => rtrim($this->prefix, '/') . '/' . ltrim($route, '/'), $routes);

      return $this->handle($this->normalizeMethods($methods), $routes, ...$handlers);
   }

   public function __call($method, $args)
   {
      $routes = !\is_array($args[0]) ? [$args[0]] : $args[0];
      unset($args[0]);

      $handlers = [...$this->middlewares, ...$args];
      $routes = array_map(fn($route) => rtrim($this->prefix, '/') . '/' . ltrim($route, '/'), $routes);

      return match ($method) {
         'get',
         'put',
         'post',
         'head',
         'patch',
         'delete' => $this->handle($method, $routes, ...$handlers),
         default => throw new \BadMethodCallException("Method {$method} does not exist in " . __CLASS__),
      };
   }

   private function handleHandler(callable|array|string $handler, &$request, $response, Closure $next) {
      return \call_user_func($handler, $request, $response, $next);
   }

   private function normalizeMethods(string|array $methods): string
   {
      if (\is_array($methods)) {
         $list = array_map(fn($method) => strtoupper((string) $method), $methods);
         $list = array_filter($list, fn($method) => $method !== '');

         return implode('|', array_values(array_unique($list)));
      }

      return strtoupper($methods);
   }

   private function handle(string $method, array $route, callable|array|string ...$handlers)
   {
      $response = new Response();
      $iterator = 0;

      $map = new MapRoute($method, $route, $this->caseSensitive)->match();

      if ($map) {
         $this->matched = true;
         $request = new HttpRequest($map['params'] ?? []);

         $next = function() use (&$iterator, $handlers, &$request, $response, &$next) {
            if (++$iterator >= \count($handlers)) return;
            return $this->handleHandler($handlers[$iterator], $request, $response, $next);
         };

         $output = $this->handleHandler($handlers[$iterator], $request, $response, $next);

			if ($output !== null) {
            if ($output instanceof Response) {
               if ($this->return) {
                  $this->matchedOutput = $output->__toString();
                  return $this->matchedOutput;
               }
               $output->send();
            }

				if ($this->return) {
               $this->matchedOutput = $output;
               return $output;
            }
				echo $output;
				exit;
			}

         if ($this->return) {
            return $this->matchedOutput = '';
         }
      }
   }
}
