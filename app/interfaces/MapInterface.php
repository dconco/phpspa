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
 * @package PhpSPA\Interfaces
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 */
interface MapInterface
{
    /**
     * Validating $route methods
     *
     * @param string $method
     * @param string|array $route
     */
    public function match(string $method, string|array $route, bool $caseSensitive): bool|array;
}
