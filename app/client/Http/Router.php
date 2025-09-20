<?php

namespace phpSPA\Http;

/**
 * Handles routing for the application.
 * 
 * @category phpSPA\Http
 * @author Samuel Paschalson <your@email.com>
 * @copyright 2025 Samuel Paschalson
 * @see https://phpspa.readthedocs.io/en/latest/response-handling
 */

class Router {
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
     * Set the base path for the router.
     *
     * @param string $path The base path.
     * @return void
     */
    public static function setBasePath(string $path): void {
        self::$basePath = rtrim($path, '/');
    }

    /**
     * Register a GET route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function get(string $uri, callable $callback) {
        self::$routes['GET'][$uri] = $callback;
    }

    /**
     * Register a POST route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function post(string $uri, callable $callback) {
        self::$routes['POST'][$uri] = $callback;
    }

    /**
     * Register a PUT route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function put(string $uri, callable $callback) {
        self::$routes['PUT'][$uri] = $callback;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function delete(string $uri, callable $callback) {
        self::$routes['DELETE'][$uri] = $callback;
    }

    /**
     * Register a PATCH route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function patch(string $uri, callable $callback) {
        self::$routes['PATCH'][$uri] = $callback;
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function options(string $uri, callable $callback) {
        self::$routes['OPTIONS'][$uri] = $callback;
    }

    /**
     * Register a HEAD route.
     *
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public static function head(string $uri, callable $callback) {
        self::$routes['HEAD'][$uri] = $callback;
    }

    /**
     * Handle the incoming request and dispatch to the appropriate route.
     * This method now automatically sends the response.
     *
     * @param Request $request
     * @return void
     */
    public static function handle(Request $request): void {
        $method = $request->method();
        $uri = $request->getUri();

        // Remove base path from URI if set
        if (self::$basePath && strpos($uri, self::$basePath) === 0) {
            $uri = substr($uri, strlen(self::$basePath));
        }

        // Remove trailing slash
        $uri = rtrim($uri, '/');

        // Check if the exact route exists
        if (isset(self::$routes[$method][$uri])) {
            $callback = self::$routes[$method][$uri];
            $response = $callback($request);
            $response->send();
            return;
        }

        // Handle dynamic routes with parameters
        foreach (self::$routes[$method] as $route => $callback) {
            // Convert route pattern to regex
            $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = "#^{$pattern}$#";

            if (preg_match($pattern, $uri, $matches)) {
                // Remove numeric keys (full pattern matches)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Pass the request and parameters to the callback
                $response = $callback($request, ...array_values($params));
                $response->send();
                return;
            }
        }

        // Return 404 if no route found
        $response = response()->error('Not Found', 404);
        $response->send();
    }
}

/**
 * Global router helper function.
 *
 * @return Router
 */
function router(): Router {
    return new Router();
}
