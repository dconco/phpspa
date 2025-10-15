<?php

/**
 * Auto-detect and set the application base path
 *
 * @return string The detected base path
 */
function autoDetectBasePath(): string
{
    return \PhpSPA\Core\Helper\PathResolver::autoDetectBasePath();
}

/**
 * Get the relative path from current URI to base path
 *
 * @param string|null $requestUri Request URI (defaults to current)
 * @return string Relative path to base (e.g., '../../')
 */
function relativePath(?string $requestUri = null): string
{
    if ($requestUri === null) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    }
    
    return \PhpSPA\Core\Helper\PathResolver::getRelativePathFromUri($requestUri);
}

/**
 * Resolve a path relative to the application base
 *
 * @param string $path Path to resolve
 * @param bool $includeBasePath Whether to include base path
 * @return string Resolved path
 */
function resolvePath(string $path, bool $includeBasePath = true): string
{
    return \PhpSPA\Core\Helper\PathResolver::resolve($path, $includeBasePath);
}

/**
 * Get the base path of the application
 *
 * @return string Base path
 */
function basePath(): string
{
    return \PhpSPA\Core\Helper\PathResolver::getBasePath();
}