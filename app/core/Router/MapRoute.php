<?php

declare(strict_types=1);

namespace PhpSPA\Core\Router;

use PhpSPA\App;
use PhpSPA\Interfaces\MapInterface;

/**
 * Class MapRoute
 *
 * This class is responsible for mapping and matching routes against the current request URI and HTTP method.
 * It extends the Controller class and implements the MapInterface interface.
 *
 * @author dconco <info@dconco.dev>
 * @copyright 2025 Dave Conco
 * @package PhpSPA\Core\Router
 * @license MIT
 * @var string|array $route
 * @var string $request_uri
 * @var array $method
 * @var bool $caseSensitive
 * @package PhpSPA\Core\Router
 * @static
 */
class MapRoute implements MapInterface
{
    use \PhpSPA\Core\Utils\Validate;
    use \PhpSPA\Core\Utils\Routes\StrictTypes;

    /**
     * @var string|array $route The route(s) to be mapped.
     */
    private static string|array $route;

    /**
     * @var string $request_uri The URI of the current request.
     */
    private static string $request_uri;

    /**
     * @var array $method An array to store HTTP methods for routing.
     */
    private static array $method;

    /**
     * Sets the case sensitivity for route matching.
     *
     * @param bool $caseSensitive If true, route matching will be case sensitive.
     */
    private static bool $caseSensitive;

    /**
     * Matches the given HTTP method and route against the current request URI.
     *
     * @param string $method The HTTP method(s) to match, separated by '|'.
     * @param string|array $route The route pattern(s) to match.
     * @param bool $caseSensitive Set if routes match should be case-sensitive
     * @return bool|array Returns false if no match is found, or an array with the matched method, route, and parameters if a match is found.
     *
     * The function performs the following steps:
     * - Sets the HTTP method(s) to match.
     * - Normalizes the request URI by removing leading and trailing slashes and converting to lowercase.
     * - Normalizes the route pattern(s) by removing leading and trailing slashes and converting to lowercase.
     * - Checks if the route contains a pattern and resolves it if necessary.
     * - Extracts parameter names from the route pattern.
     * - Matches the request URI against the route pattern and extracts parameter values.
     * - Constructs a regex pattern to match the route.
     * - Checks if the request method is allowed for the matched route.
     * - Returns an array with the matched method, route, and parameters if a match is found.
     * - Returns false if no match is found.
     */
    public function match(string $method, string|array $route, bool $caseSensitive): bool|array
    {
        self::$method = explode('|', $method);
        self::$method = array_map('trim', self::$method);
        self::$method = array_map('strtoupper', self::$method);
        self::$caseSensitive = $caseSensitive;

        /**
         *   ----------------------------------------------
         *   |   Replacing first and last forward slashes
         *   |   $_REQUEST['uri'] will be empty if req uri is /
         *   ----------------------------------------------
         */
        self::$request_uri = preg_replace("/(^\/)|(\/$)/", '', App::$request_uri);
        self::$request_uri = empty(self::$request_uri) ? '/' : self::$request_uri;

        // If caseSensitive is false, convert request URI to lowercase
        self::$request_uri = $caseSensitive
         ? self::$request_uri
         : strtolower(self::$request_uri);

        self::$route = is_array($route)
         ? $route
         : preg_replace("/(^\/)|(\/$)/", '', $caseSensitive ? $route : strtolower($route));

        //  Firstly, resolve route with pattern
        if (is_array(self::$route)) {
            foreach (self::$route as $value) {
                if (str_starts_with($value, 'pattern:')) {
                    if ($p = $this->pattern()) {
                        return $p;
                    }
                }
            }
        } elseif (str_starts_with(self::$route, 'pattern:')) {
            return $this->pattern();
        }

        // will store all the parameters value in this array
        $req = [];
        $unvalidate_req = [];
        $req_value = [];

        // will store all the parameters names in this array
        $paramKey = [];

        // finding if there is any {?} parameter in $route
        if (is_string(self::$route)) {
            preg_match_all('/(?<={).+?(?=})/', self::$route, $paramMatches);
        }

        // if the route does not contain any param call routing();
        if (
            empty($paramMatches) ||
            empty($paramMatches[0] ?? []) ||
            is_array(self::$route)
        ) {
            /**
             *   ------------------------------------------------------
             *   |   Check if $callback is a callable function
             *   |   or array of controller, and if not,
             *   |   it's a string of text or html document
             *   ------------------------------------------------------
             */
            return $this->match_routing();
        }

        // setting parameters names
        foreach ($paramMatches[0] as $key) {
            $paramKey[] = $key;
        }

        // exploding route address
        $uri = explode('/', self::$route);

        // will store index number where {?} parameter is required in the $route
        $indexNum = [];

        // storing index number, where {?} parameter is required with the help of regex
        foreach ($uri as $index => $param) {
            if (preg_match('/{.*}/', $param)) {
                $indexNum[] = $index;
            }
        }

        /**
         *   ----------------------------------------------------------------------------------
         *   |   Exploding request uri string to array to get the exact index number value of parameter from $_REQUEST['uri']
         *   ----------------------------------------------------------------------------------
         */
        $reqUri = explode('/', self::$request_uri);
        /**
         *   ----------------------------------------------------------------------------------
         *   |   Running for each loop to set the exact index number with reg expression this will help in matching route
         *   ----------------------------------------------------------------------------------
         */
        foreach ($indexNum as $key => $index) {
            /**
             *   --------------------------------------------------------------------------------
             *   |   In case if req uri with param index is empty then return because URL is not valid for this route
             *   --------------------------------------------------------------------------------
             */

            if (empty($reqUri[$index])) {
                return false;
            }

            if (str_contains($paramKey[$key], ':')) {
                $unvalidate_req[] = [ $paramKey[$key], $reqUri[$index] ];
            }

            // setting params with params names
            $key = trim((string) explode(':', $paramKey[$key], 2)[0]);
            $req[$key] = $reqUri[$index];
            $req_value[] = $reqUri[$index];

            // this is to create a regex for comparing route address
            $reqUri[$index] = '{.*}';
        }
        // converting array to string
        $reqUri = implode('/', $reqUri);

        /**
         *   -----------------------------------
         *   |   replace all / with \/ for reg expression
         *   |   regex to match route is ready!
         *   -----------------------------------
         */
        $reqUri = str_replace('/', '\\/', $reqUri);

        // now matching route with regex
        if (preg_match("/$reqUri/", self::$route . '$')) {
            if (!empty($unvalidate_req)) {
                foreach ($unvalidate_req as $value) {
                    $param_name = trim((string) explode(':', $value[0], 2)[0]);
                    $param_types = trim((string) explode(':', $value[0], 2)[1]);
                    $param_types = preg_split('/\|(?![^<]*>)/', $param_types);
                    $param_value = $value[1];

                    $parsed_value = static::matchStrictType(
                        $param_value,
                        $param_types,
                    );

                    if (!$parsed_value) {
                        return false;
                    }

                    // checks if the requested method is of the given route
                    if (
                        !in_array(strtoupper($_SERVER['REQUEST_METHOD']), self::$method) &&
                        !in_array('*', self::$method)
                    ) {
                        // http_response_code(405);
                        // exit('Method Not Allowed');
                        return false;
                    }

                    $req[$param_name] = $parsed_value;
                }
            }

            return [
             'method' => strtoupper($_SERVER['REQUEST_METHOD']),
             'route' => self::$route,
             'params_value' => $req_value,
             'params' => $req,
            ];
        }

        return false;
    }

    /**
     * Matches the current request URI and method against the defined routes.
     *
     * This method checks if the current request URI matches any of the defined routes
     * and if the request method is allowed for the matched route. If a match is found,
     * it returns an array containing the request method and the matched route. If no
     * match is found, it returns false.
     *
     * @return bool|array Returns an array with 'method' and 'route' keys if a match is found, otherwise false.
     */
    private function match_routing(): bool|array
    {
        $uri = [];
        $str_route = '';

        if (is_array(self::$route)) {
            for ($i = 0; $i < count(self::$route); $i++) {
                $each_route = preg_replace("/(^\/)|(\/$)/", '', self::$route[$i]);

                empty($each_route)
                 ? array_push($uri, '/')
                 : array_push($uri, strtolower(self::$caseSensitive ? $each_route : strtolower($each_route)));
            }
        } else {
            $str_route = empty(self::$route) ? '/' : self::$route;
        }

        if (
            in_array(self::$request_uri, $uri) ||
            self::$request_uri === $str_route
        ) {
            if (
                !in_array(strtoupper($_SERVER['REQUEST_METHOD']), self::$method) &&
                !in_array('*', self::$method)
            ) {
                // http_response_code(405);
                // exit('Method Not Allowed');
                return false;
            }

            return [
             'method' => strtoupper($_SERVER['REQUEST_METHOD']),
             'route' => self::$route,
            ];
        } else {
            return false;
        }
    }

    /**
     * Validates and matches a route pattern.
     *
     * This method checks if the route is an array and iterates through each value to find a pattern match.
     * If a pattern match is found, it validates the pattern and returns the matched result.
     * If no match is found in the array, it returns false.
     * If the route is not an array, it directly validates the pattern and returns the result.
     *
     * @return array|bool The matched pattern as an array if found, or false if no match is found.
     */
    private function pattern(): array|bool
    {
        if (is_array(self::$route)) {
            foreach (self::$route as $value) {
                if (str_starts_with('pattern:', $value)) {
                    $matched = $this->validatePattern($value);

                    if ($matched) {
                        return $matched;
                    }
                }
            }
            return false;
        }

        return $this->validatePattern(self::$route);
    }

    /**
     * Validates the given pattern against the request URI and checks the request method.
     *
     * @param string $pattern The pattern to validate.
     * @return array|bool Returns an array with the request method and route if the pattern matches, otherwise false.
     */
    private function validatePattern(string $pattern): array|bool
    {
        $pattern = preg_replace("/(^\/)|(\/$)/", '', trim(substr($pattern, 8)));

        if (fnmatch($pattern, self::$request_uri)) {
            if (
                !in_array($_SERVER['REQUEST_METHOD'], self::$method) &&
                !in_array('*', self::$method)
            ) {
                // http_response_code(405);
                // exit('Method Not Allowed');
                return false;
            }

            return [
             'method' => $_SERVER['REQUEST_METHOD'],
             'route' => self::$route,
            ];
        }
        return false;
    }
}
