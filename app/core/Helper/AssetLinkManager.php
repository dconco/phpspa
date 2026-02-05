<?php

declare(strict_types=1);

namespace PhpSPA\Core\Helper;

use PhpSPA\Core\Helper\PathResolver;
use PhpSPA\Http\Response;

/**
 * Asset Link Manager
 *
 * Manages the generation of encoded links for CSS and JavaScript assets.
 * Encodes asset details in the URL with HMAC for integrity and stateless asset serving with expiration validation.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.1.6
 */
class AssetLinkManager
{
    /**
     * Default cache duration in hours
     */
    private const DEFAULT_CACHE_HOURS = 24;

    /**
     * Secret key for HMAC
     */
    private static string $secretKey = 'phpspa_asset_secret_key_2026';

    /**
     * Cache duration in hours
     */
    private static int $cacheHours = self::DEFAULT_CACHE_HOURS;

    /**
     * Generate a unique encoded link for a CSS asset
     *
     * @param string $componentRoute The route identifier for the component
     * @param int $stylesheetIndex The index of the stylesheet in the component's stylesheets array
     * @param int $filemtime The file modification time of the asset
     * @param string|null $name Optional name for the asset
     * @return string The generated CSS link
     */
    public static function generateCssLink(string $componentRoute, int $stylesheetIndex, int $filemtime, ?string $name = null): string
    {
        $data = [
            'componentRoute' => $componentRoute,
            'assetType' => 'css',
            'assetIndex' => $stylesheetIndex,
            'name' => $name,
            'created' => self::getCacheBucketTimestamp(),
            'scriptType' => 'text/css',
            'version' => $filemtime
        ];

        $encoded = self::encodeAssetData($data);
        return self::buildAssetUrl($encoded, 'css', $name);
    }

    /**
     * Generate a unique encoded link for a JavaScript asset
     *
     * @param string $componentRoute The route identifier for the component
     * @param int $scriptIndex The index of the script in the component's scripts array
     * @param int $filemtime The file modification time of the asset
     * @param string|null $name Optional name for the asset
     * @param string $type The type of the script
     * @return string The generated JS link
     */
    public static function generateJsLink(string $componentRoute, int $scriptIndex, int $filemtime, ?string $name = null, string $type = 'text/javascript'): string
    {
        $data = [
            'componentRoute' => $componentRoute,
            'assetType' => 'js',
            'assetIndex' => $scriptIndex,
            'name' => $name,
            'created' => self::getCacheBucketTimestamp(),
            'scriptType' => $type,
            'version' => $filemtime
        ];

        $encoded = self::encodeAssetData($data);
        return self::buildAssetUrl($encoded, 'js', $name);
    }

    /**
     * Check if a request is for an encoded asset
     *
     * @param string $requestUri The current request URI
     * @return array|null Asset information if found, null otherwise
     */
    public static function resolveAssetRequest(string $requestUri): ?array
    {
        if (!preg_match('/\/phpspa\/assets\/(?:([^\/]+)-)?([^\/]+)\.(css|js)$/', $requestUri, $matches)) {
            return null;
        }

        $name = !empty($matches[1]) ? $matches[1] : null;
        $encoded = $matches[2]; // This now contains the data + signature. e.g., "eyJ...~abc123"
        $type = $matches[3];

        $data = self::decodeAssetData($encoded);

        if (!$data || $data['name'] !== $name || $data['assetType'] !== $type) {
            return null;
        }

        // Check if mapping has expired
        if (self::isMappingExpired($data['created'])) {
            http_response_code(Response::StatusGone);
            header('Content-Type: text/plain');
            echo "Asset has expired";
            exit;
        }

        return [
            'hash' => $encoded,
            'type' => $type,
            'componentRoute' => $data['componentRoute'],
            'scriptType' => $data['scriptType'],
            'assetType' => $data['assetType'],
            'assetIndex' => $data['assetIndex'],
            'name' => $name
        ];
    }

    /**
     * Set the secret key for HMAC verification
     *
     * @param string $key The secret key
     * @return void
     */
    public static function setSecretKey(string $key): void
    {
        self::$secretKey = $key;
    }

    /**
     * Set cache configuration for assets
     *
     * @param int $hours Number of hours to cache assets (0 for no expiration)
     * @return void
     */
    public static function setCacheConfig(int $hours): void
    {
        self::$cacheHours = $hours;
    }

    /**
     * Get current cache configuration
     *
     * @return array Cache configuration
     */
    public static function getCacheConfig(): array
    {
        return [
            'hours' => self::$cacheHours,
            'timestamp' => time()
        ];
    }

    /**
     * Encode asset data for URL with HMAC for integrity
     *
     * @param array $data The asset data
     * @return string URL-encoded base64 data with HMAC signature
     */
    private static function encodeAssetData(array $data): string
    {
        $json = json_encode($data);

        // Use URL-safe Base64: + becomes -, / becomes _
        $base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($json));

        $signature = hash_hmac('sha256', $base64, self::$secretKey);
        $sigShort = substr($signature, 0, 16);

        // Use '~' as the signature separator to avoid dot/extension conflicts
        return $base64 . '~' . $sigShort;
    }

    /**
     * Decode asset data from URL and verify HMAC
     *
     * @param string $encodedWithSig URL-encoded base64 data with HMAC signature
     * @return array|null Decoded data or null on failure/verification error
     */
    private static function decodeAssetData(string $encodedWithSig): ?array
    {
        if (strpos($encodedWithSig, '~') === false) return null;

        [$encoded, $signature] = explode('~', $encodedWithSig);

        // Verify the signature
        $expected = substr(hash_hmac('sha256', $encoded, self::$secretKey), 0, 16);
        if (!hash_equals($expected, $signature)) return null;

        $standardBase64 = str_replace(['-', '_'], ['+', '/'], $encoded);
        $json = base64_decode($standardBase64);
        
        return $json ? json_decode($json, true) : null;
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
     * @param int $created The creation timestamp
     * @return bool True if expired, false otherwise
     */
    private static function isMappingExpired(int $created): bool
    {
        // If cache hours is 0, never expires
        if (self::$cacheHours === 0) {
            return false;
        }

        $expireTime = $created + (self::$cacheHours * 3600);
        return time() > $expireTime;
    }

    /**
     * Get a stable timestamp bucket for cache windows.
     *
     * This prevents new asset URLs from being generated on every request while
     * still allowing periodic rotation when cacheHours > 0.
     */
    private static function getCacheBucketTimestamp(): int
    {
        if (self::$cacheHours === 0) {
            return 0;
        }

        $window = self::$cacheHours * 3600;
        return (int) (floor(time() / $window) * $window);
    }

}
