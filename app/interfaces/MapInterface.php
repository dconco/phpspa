<?php

declare(strict_types=1);

namespace PhpSPA\Interfaces;

/**
 * Route mapping interface for handling HTTP request matching
 *
 * Defines the contract for route mapping implementations that need to
 * validate and match HTTP requests against defined route patterns.
 * This interface ensures consistent route matching behavior across
 * different routing strategies.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.0.0
 */
interface MapInterface
{
    /**
     * Matches the given HTTP method and route against the current request URI.
     *
     * @param string $method The HTTP method(s) to match, separated by '|'.
     * @param array $routes The route pattern(s) to match.
     * @param bool $pattern If the route to match is in `fnmatch` pattern format
     * @param bool $caseSensitive Set if routes match should be case-sensitive
    */
    public function __construct(string $method, array $routes, bool $caseSensitive, bool $pattern);

    /**
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
     * 
     * @return bool|array Returns false if no match is found, or an array with the matched method, route, and parameters if a match is found.
     */
    public function match(): array|bool;
}
