<?php

namespace phpSPA\Http;

use phpSPA\App;
use phpSPA\Core\Router\MapRoute;

/**
 * Handles routing for the application.
 * 
 * @category phpSPA\Http
 * @author Samuel Paschalson <samuelpaschalson@gmail.com>
 * @copyright 2025 Samuel Paschalson
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.8
 */

class Router
{
    /**
     * @var array Registered routes
     */
    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
        'OPTIONS' => [],
        'HEAD' => []
    ];

    /**
     * @var string Base path for the router
     */
    private static $basePath = '/api';

    /**
     * @var bool Whether the shutdown handler has been registered
     */
    private static $shutdownHandlerRegistered = false;

    /**
     * @var bool Whether routes are case sensitive
     */
    private static $caseSensitive = false;

    /**
     * Set the base path for the router.
     *
     * @param string $path The base path.
     * @return void
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    /**
     * Set whether routes are case sensitive.
     *
     * @param bool $caseSensitive
     * @return void
     */
    public static function setCaseSensitive(bool $caseSensitive): void
    {
        self::$caseSensitive = $caseSensitive;
    }

    /**
     * Register a GET route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function get(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['GET'][$uri] = $callback;
    }

    /**
     * Register a POST route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function post(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['POST'][$uri] = $callback;
    }

    /**
     * Register a PUT route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function put(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['PUT'][$uri] = $callback;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function delete(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['DELETE'][$uri] = $callback;
    }

    /**
     * Register a PATCH route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function patch(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['PATCH'][$uri] = $callback;
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function options(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['OPTIONS'][$uri] = $callback;
    }

    /**
     * Register a HEAD route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function head(string $uri, callable $callback, ?Request $request = null)
    {
        self::ensureShutdownHandlerRegistered($request);
        self::$routes['HEAD'][$uri] = $callback;
    }

    /**
     * Ensure the shutdown handler is registered once.
     *
     * @return void
     */
    private static function ensureShutdownHandlerRegistered(?Request $request): void
    {
        if (self::$shutdownHandlerRegistered) return;
        $request = $request ?? new Request();

        register_shutdown_function([self::class, 'handle'], $request);
        self::$shutdownHandlerRegistered = true;
    }

    /**
     * Handle the incoming request and dispatch to the appropriate route.
     * This method is intended to be invoked at PHP shutdown via register_shutdown_function.
     *
     * @param Request $request
     * @return void
     */
    private static function handle(Request $request): void
    {
        $method = $request->method();
        $uri = $request->getUri();

        // Let MapRoute handle matching (supports patterns, types, and case rules)
        // Set the application request URI so MapRoute can access it
        App::$request_uri = $uri;

        $mapper = new MapRoute();

        foreach (self::$routes[$method] as $route => $callback) {
            $route = rtrim(self::$basePath) . '/' . ltrim($route, '/');
            $match = $mapper->match($method, $route, self::$caseSensitive);
            if (!$match) continue;

            // If MapRoute returned parameters, pass them to the callback
            if (!empty($match['params']) && is_array($match['params'])) {
                $response = $callback($request, ...array_values($match['params']));
            } else {
                $response = $callback($request);
            }

            $response->send();
            return;
        }

        // Return 404 if no route found
        $response = response()->error('Not Found', 404);
        $response->send();
    }
}
