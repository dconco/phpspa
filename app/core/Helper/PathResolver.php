<?php

namespace PhpSPA\Core\Helper;

/**
 * Path resolver utility for calculating relative paths
 *
 * This class helps resolve relative paths between a base path and current path,
 * useful for generating correct URLs in nested routes.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class PathResolver
{
   /**
    * @var string The base path of the application
    */
   private static string $basePath = '';

   /**
    * Set the base path for the application
    *
    * @param string $basePath The base path (e.g., '/template/web')
    * @return void
    */
   public static function setBasePath(string $basePath): void
   {
      self::$basePath = rtrim($basePath, '/');
   }

   /**
    * Get the current base path
    *
    * @return string
    */
   public static function getBasePath(): string
   {
      return self::$basePath;
   }

   /**
    * Extract base path from request URI and script path automatically
    *
    * @param string $requestUri The full request URI (from $_SERVER['REQUEST_URI'])
    * @param string $scriptPath The script path (from $_SERVER['SCRIPT_NAME'])
    * @return string The extracted base path
    */
   public static function extractBasePath(string $requestUri, string $scriptPath = ''): string
   {
      // Remove query string / fragment from request URI (keep leading '//' intact).
      // NOTE: parse_url() treats leading '//' as scheme-relative URL and will drop it.
      $requestUri = (string) preg_split('/[?#]/', $requestUri, 2)[0];

      // Get directory of script path (where the entry point is located)
      $scriptDir = dirname($scriptPath);

      // Normalize paths - convert backslashes to forward slashes
      $scriptDir = str_replace('\\', '/', $scriptDir);
      $requestUri = str_replace('\\', '/', $requestUri);

      // If script is in web root, base path is empty
      $basePath = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : $scriptDir;

      // Treat every '/' as a real segment boundary (including repeated slashes).
      // Rationale: browsers resolve relative URLs by walking the path segments. If the URL contains
      // multiple slashes (e.g. "////"), the path contains empty segments, but the separators still
      // contribute to how many "../" hops are needed. Since we generate asset URLs as relative paths,
      // we must mirror the browser's path resolution behavior to avoid broken links on routes like:
      // //dashboard////  => ../../../../../
      $path = $requestUri;
      $endsWithSlash = $path !== '' && substr($path, -1) === '/';

      $segments = explode('/', $path);

      // Build the directory segments of the current URL.
      // - If URL ends with '/', it's already a directory URL: drop only the trailing empty segment.
      // - Otherwise, drop the last segment (treat it as a resource segment).
      if ($endsWithSlash) {
         if (!empty($segments)) {
            $lastIndex = \count($segments) - 1;
            if ($segments[$lastIndex] === '') {
               array_pop($segments);
            }
         }
      } else {
         if (\count($segments) > 0) {
            array_pop($segments);
         }
      }

      // Depth is the number of directory segments after the leading empty segment (from the initial '/').
      $depth = max(0, \count($segments) - 1);
      $relativePrefix = $depth > 0 ? str_repeat('../', $depth) : '';

      if ($relativePrefix !== '') {
         if ($basePath === '') {
            return $relativePrefix;
         }

         $basePath = rtrim($relativePrefix, '/') . '/' . ltrim($basePath, '/');
      }

      // The base path is the directory where the script is located
      // This works for any structure: /project/public, /template, /app/web, etc.
      return $basePath;
   }

   /**
    * Auto-detect and set base path from current request
    *
    * @return string The detected base path
    */
   public static function autoDetectBasePath(): string
   {
      $requestUri = $_SERVER['REQUEST_URI'] ?? '';
      $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

      $basePath = self::extractBasePath($requestUri, $scriptName);
      self::setBasePath($basePath);

      return $basePath;
   }

   /**
    * Calculate relative path from current path to base path
    *
    * @param string $currentPath Current path relative to base
    * @return string Relative path to base (e.g., '../../')
    */
   public static function getRelativePathToBase(string $currentPath): string
   {
      // Clean the current path
      $currentPath = trim($currentPath, '/');

      // If current path is empty or same as base, no relative path needed
      if (empty($currentPath)) {
         return './';
      }

      // Count path segments
      $segments = explode('/', $currentPath);
      $depth = \count(array_filter($segments, fn($segment) => !empty($segment)));
      
      // Generate relative path
      if ($depth === 0) {
         return './';
      }

      return str_repeat('../', $depth);
   }

   /**
    * Get relative path from current URI to base path
    *
    * @param string $requestUri Full request URI
    * @return string Relative path to base
    */
   public static function getRelativePathFromUri(string $requestUri): string
   {
      // Remove query string
      $path = strtok($requestUri, '?');

      // Remove base path from current path
      if (!empty(self::$basePath) && strpos($path, self::$basePath) === 0) {
         $relativePath = substr($path, strlen(self::$basePath));
         $relativePath = explode('/', $relativePath);
         array_pop($relativePath);
         $relativePath = implode('/', $relativePath);
      } else {
         $relativePath = $path;
      }

      return self::getRelativePathToBase($relativePath);
   }

   /**
    * Resolve a path relative to the base path
    *
    * @param string $path Path to resolve
    * @param bool $includeBasePath Whether to include base path in result
    * @return string Resolved path
    */
   public static function resolve(string $path, bool $includeBasePath = true): string
   {
      // Clean the path
      $path = ltrim($path, '/');
      
      if ($includeBasePath && !empty(self::$basePath)) {
         return self::$basePath . '/' . $path;
      }
      
      return '/' . $path;
   }

   /**
    * Get the current path relative to base path
    *
    * @param string $requestUri Full request URI
    * @return string Current path relative to base
    */
   public static function getCurrentPath(string $requestUri): string
   {
      // Remove query string
      $path = strtok($requestUri, '?');
      
      // Remove base path from current path
      if (!empty(self::$basePath) && strpos($path, self::$basePath) === 0) {
         $relativePath = substr($path, \strlen(self::$basePath));
      } else {
         $relativePath = $path;
      }
      
      return trim($relativePath, '/');
   }

   /**
    * Build absolute URL from relative path
    *
    * @param string $relativePath Relative path
    * @param string $scheme Protocol scheme (http/https)
    * @param string $host Host name
    * @return string Absolute URL
    */
   public static function buildAbsoluteUrl(string $relativePath, string $scheme = 'http', string $host = 'localhost'): string
   {
      $path = self::resolve($relativePath);
      return $scheme . '://' . $host . $path;
   }

   /**
    * Normalize a path by removing redundant separators and resolving . and .. segments
    *
    * @param string $path Path to normalize
    * @return string Normalized path
    */
   public static function normalize(string $path): string
   {
      // Convert backslashes to forward slashes
      $path = str_replace('\\', '/', $path);
      
      // Split path into segments
      $segments = explode('/', $path);
      $normalized = [];
      
      foreach ($segments as $segment) {
         if ($segment === '' || $segment === '.') {
            continue; // Skip empty and current directory segments
         } elseif ($segment === '..') {
            // Go up one directory
            if (!empty($normalized)) {
               array_pop($normalized);
            }
         } else {
            $normalized[] = $segment;
         }
      }
      
      // Reconstruct path
      $result = implode('/', $normalized);
      
      // Preserve leading slash if original had one
      if (strpos($path, '/') === 0) {
         $result = '/' . $result;
      }
      
      return $result;
   }
}