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
 * @category HTTP
 * @author Samuel Paschalson <samuelpaschalson@gmail.com>
 * @copyright 2025 Samuel Paschalson
 * @method void get(string|array $route, callable ...$handlers)
 * @method void put(string|array $route, callable ...$handlers)
 * @method void post(string|array $route, callable ...$handlers)
 * @method void head(string|array $route, callable ...$handlers)
 * @method void patch(string|array $route, callable ...$handlers)
 * @method void delete(string|array $route, callable ...$handlers)
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
      static::$request_uri ??= new HttpRequest()->getUri();
   }

   /**
    * Set whether routes are case sensitive.
    */
   public function caseSensitive(bool $value): void
   {
      $this->caseSensitive = $value;
   }

   public function middleware(callable $handler) {
      $this->middlewares[] = $handler;
   }

   public function prefix(string $path, callable $handler) {
      $prefix = ['path' => rtrim($this->prefix, '/') . '/' . ltrim($path, '/'), 'handler' => $handler];
      $this->handlePrefix($prefix, $this->middlewares);
   }

   public function __call($method, $args)
   {
      $routes = !is_array($args[0]) ? [$args[0]] : [$args[0]];
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
      return call_user_func($handler, $request, $response, $next);
   }

   private function handle(string $method, array $route, callable ...$handlers): void
   {
      $response = new Response();
      $iterator = 0;

      $map = (new MapRoute($method, $route, $this->caseSensitive))->match();

      if ($map) {
         $request = new HttpRequest($map['params'] ?? []);

         $next = function() use (&$iterator, $handlers, &$request, $response, &$next) {
            if (++$iterator >= count($handlers)) return;
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
