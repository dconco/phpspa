<?php

declare(strict_types=1);

namespace PhpSPA\Core\Helper;

use PhpSPA\Core\Helper\PathResolver;
use PhpSPA\Http\Response;
use PhpSPA\Http\Session;

/**
 * Asset Link Manager
 *
 * Manages the generation and storage of session-based links for CSS and JavaScript assets.
 * Provides functionality to store function references via component routes and serve
 * compressed content through dynamic links.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.1.6
 */
class AssetLinkManager
{
    /**
     * Session key for storing asset mappings
     */
    private const ASSET_MAPPINGS_KEY = 'phpspa_asset_mappings';

    /**
     * Session key for storing cache configuration
     */
    private const CACHE_CONFIG_KEY = 'phpspa_cache_config';

    /**
     * Default cache duration in hours
     */
    private const DEFAULT_CACHE_HOURS = 24;

    /**
     * Generate a unique session-based link for a CSS asset
     *
     * @param string $componentRoute The route identifier for the component
     * @param int $stylesheetIndex The index of the stylesheet in the component's stylesheets array
     * @param string|null $name Optional name for the asset
     * @return string The generated CSS link
     */
    public static function generateCssLink(string $componentRoute, int $stylesheetIndex, ?string $name = null): string
    {
        $mappings = Session::get(self::ASSET_MAPPINGS_KEY, []);

        foreach ($mappings as $hash => $mapping) {
            if (!self::isMappingExpired($mapping) && $mapping['componentRoute'] === $componentRoute && $mapping['assetType'] === 'css' && $mapping['assetIndex'] === $stylesheetIndex) {
                return self::buildAssetUrl($hash, 'css', $name);
            }
        }

        $hash = self::generateAssetHash($componentRoute, 'css', $stylesheetIndex, $name);
        self::storeAssetMapping($hash, $componentRoute, 'css', $stylesheetIndex, $name);

        return self::buildAssetUrl($hash, 'css', $name);
    }

    /**
     * Generate a unique session-based link for a JavaScript asset
     *
     * @param string $componentRoute The route identifier for the component
     * @param int $scriptIndex The index of the script in the component's scripts array
     * @param string|null $name Optional name for the asset
     * @param string $type The type of the script
     * @return string The generated JS link
     */
    public static function generateJsLink(string $componentRoute, int $scriptIndex, ?string $name = null, string $type = 'text/javascript'): string
    {
        $mappings = Session::get(self::ASSET_MAPPINGS_KEY, []);

        foreach ($mappings as $hash => $mapping) {
            if (!self::isMappingExpired($mapping) && $mapping['componentRoute'] === $componentRoute && $mapping['assetType'] === 'js' && $mapping['assetIndex'] === $scriptIndex) {
                return self::buildAssetUrl($hash, 'js', $name);
            }
        }

        $hash = self::generateAssetHash($componentRoute, 'js', $scriptIndex, $name, $type);
        self::storeAssetMapping($hash, $componentRoute, 'js', $scriptIndex, $name, $type);

        return self::buildAssetUrl($hash, 'js', $name);
    }

    /**
     * Check if a request is for a session-based asset
     *
     * @param string $requestUri The current request URI
     * @return array|null Asset information if found, null otherwise
     */
    public static function resolveAssetRequest(string $requestUri): ?array
    {
        if (!preg_match('/\/phpspa\/assets\/(?:(.+)-([a-f0-9]{32})|([a-f0-9]{32}))\.(css|js)$/', $requestUri, $matches)) {
            return null;
        }

        if (!empty($matches[1])) {
            $name = $matches[1];
            $hash = $matches[2];
            $type = $matches[4];
        } else {
            $name = null;
            $hash = $matches[3];
            $type = $matches[4];
        }
        
        $mappings = Session::get(self::ASSET_MAPPINGS_KEY, []);
        
        if (!isset($mappings[$hash])) {
            http_response_code(Response::StatusNotFound);
            header('Content-Type: text/plain');
            echo "Asset not found";
            exit;
        }
        
        $mapping = $mappings[$hash];
        
        // Check if mapping has expired
        if (self::isMappingExpired($mapping)) {
            unset($mappings[$hash]);
            Session::set(self::ASSET_MAPPINGS_KEY, $mappings, true);

            http_response_code(Response::StatusGone);
            header('Content-Type: text/plain');
            echo "Asset has expired";
            exit;
        }

        return [
            'hash' => $hash,
            'type' => $type,
            'componentRoute' => $mapping['componentRoute'],
            'scriptType' => $mapping['scriptType'],
            'assetType' => $mapping['assetType'],
            'assetIndex' => $mapping['assetIndex']
        ];
    }

    /**
     * Set cache configuration for assets
     *
     * @param int $hours Number of hours to cache assets (0 for session-only)
     * @return void
     */
    public static function setCacheConfig(int $hours): void
    {
        Session::set(self::CACHE_CONFIG_KEY, [
            'hours' => $hours,
            'timestamp' => time()
        ]);
    }

    /**
     * Get current cache configuration
     *
     * @return array Cache configuration
     */
    public static function getCacheConfig(): array
    {
        return Session::get(self::CACHE_CONFIG_KEY, [
            'hours' => self::DEFAULT_CACHE_HOURS,
            'timestamp' => time()
        ]);
    }

    /**
     * Generate a unique hash for an asset
     *
     * @param string $componentRoute The component route
     * @param string $assetType Type of asset ('css' or 'js')
     * @param int $assetIndex Index of the asset
     * @param string|null $name Optional name for the asset
     * @return string Generated hash
     */
    private static function generateAssetHash(string $componentRoute, string $assetType, int $assetIndex, ?string $name = null, ?string $type = null): string
    {
        $sessionId = session_id();
        $data = $sessionId . $componentRoute . $assetType . $assetIndex;

        if ($name) $data .= $name;
        if ($type) $data .= $type;

        return md5($data);
    }

    /**
     * Store asset mapping in session
     *
     * @param string $hash The asset hash
     * @param string $componentRoute The component route
     * @param string $assetType Type of asset ('css' or 'js')
     * @param int $assetIndex Index of the asset
     * @param string|null $name Optional name for the asset
     * @return void
     */
    private static function storeAssetMapping(string $hash, string $componentRoute, string $assetType, int $assetIndex, ?string $name = null, ?string $type = null): void
    {
        $mappings = Session::get(self::ASSET_MAPPINGS_KEY, []);

        $mappings[$hash] = [
            'componentRoute' => $componentRoute,
            'assetType' => $assetType,
            'assetIndex' => $assetIndex,
            'name' => $name,
            'created' => time(),
            'scriptType' => $type ?? 'text/javascript'
        ];

        Session::set(self::ASSET_MAPPINGS_KEY, $mappings, true);
    }

    /**
     * Build the asset URL using absolute or relative paths based on base path
     *
     * @param string $hash The asset hash
     * @param string $type Asset type ('css' or 'js')
     * @param string|null $name Optional name for the asset
     * @return string The complete asset URL (absolute if base path exists, relative otherwise)
     */
    private static function buildAssetUrl(string $hash, string $type, ?string $name = null): string
    {
        // Auto-detect base path if not set
        if (empty(PathResolver::getBasePath())) {
            PathResolver::autoDetectBasePath();
        }

        $basePath = PathResolver::getBasePath();
        $filename = $name ? "{$name}-{$hash}" : $hash;
        $assetPath = "phpspa/assets/{$filename}.{$type}";
        
        // If there's a base path (server started from nested directory), use absolute URL
        if (!empty($basePath)) {
            return "{$basePath}/{$assetPath}";
        }
        
        // If base path is empty (server started from root), use relative path
        $relativePath = PathResolver::getRelativePathFromUri($_SERVER['REQUEST_URI'] ?? '');
        return "{$relativePath}{$assetPath}";
    }

    /**
     * Get the base URL for the application
     *
     * @return string Base URL
     */
    private static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Check if an asset mapping has expired
     *
     * @param array $mapping The asset mapping
     * @return bool True if expired, false otherwise
     */
    private static function isMappingExpired(array $mapping): bool
    {
        $cacheConfig = self::getCacheConfig();

        // If cache hours is 0, use session-only (never expires until session ends)
        if ($cacheConfig['hours'] === 0) {
            return false;
        }

        $expireTime = $mapping['created'] + ($cacheConfig['hours'] * 3600);
        return time() > $expireTime;
    }

    /**
     * Clean up expired asset mappings
     *
     * @return void
     */
    public static function cleanupExpiredMappings(): void
    {
        $mappings = Session::get(self::ASSET_MAPPINGS_KEY, []);
        $cleaned = false;

        foreach ($mappings as $hash => $mapping) {
            if (self::isMappingExpired($mapping)) {
                unset($mappings[$hash]);
                $cleaned = true;
            }
        }

        if ($cleaned) {
            Session::set(self::ASSET_MAPPINGS_KEY, $mappings, true);
        }
    }
}
