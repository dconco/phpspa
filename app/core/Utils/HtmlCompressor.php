<?php

namespace phpSPA\Core\Utils;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for phpSPA
 * to reduce payload sizes and improve performance.
 *
 * @package phpSPA\Core\Utils
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 */
class HtmlCompressor
{
    /**
     * Compression levels
     */
    public const LEVEL_NONE = 0;
    public const LEVEL_BASIC = 1;
    public const LEVEL_AGGRESSIVE = 2;
    public const LEVEL_EXTREME = 3;

    /**
     * Current compression level
     *
     * @var int
     */
    private static int $compressionLevel = self::LEVEL_BASIC;

    /**
     * Whether to use gzip compression
     *
     * @var bool
     */
    private static bool $useGzip = true;

    /**
     * Set compression level
     *
     * @param int $level Compression level (0-3)
     * @return void
     */
    public static function setLevel(int $level): void
    {
        self::$compressionLevel = max(0, min(3, $level));
    }

    /**
     * Enable or disable gzip compression
     *
     * @param bool $enabled Whether to use gzip
     * @return void
     */
    public static function setGzipEnabled(bool $enabled): void
    {
        self::$useGzip = $enabled;
    }

    /**
     * Compress HTML content
     *
     * @param string $html HTML content to compress
     * @return string Compressed HTML
     */
    public static function compress(string $html): string
    {
        if (self::$compressionLevel === self::LEVEL_NONE) {
            return $html;
        }

        // Apply minification based on compression level
        $html = self::minify($html, self::$compressionLevel);

        // Apply gzip compression if enabled and supported
        if (self::$useGzip && self::supportsGzip()) {
            return self::gzipCompress($html);
        }

        return $html;
    }

    /**
     * Minify HTML content
     *
     * @param string $html HTML content
     * @param int $level Compression level
     * @return string Minified HTML
     */
    private static function minify(string $html, int $level): string
    {
        switch ($level) {
            case self::LEVEL_BASIC:
                return self::basicMinify($html);

            case self::LEVEL_AGGRESSIVE:
                return self::aggressiveMinify($html);

            case self::LEVEL_EXTREME:
                return self::extremeMinify($html);

            default:
                return $html;
        }
    }

    /**
     * Basic HTML minification
     *
     * @param string $html HTML content
     * @return string Minified HTML
     */
    private static function basicMinify(string $html): string
    {
        // Remove HTML comments (but preserve IE conditional comments)
        $html = preg_replace('/<!--(?!\s*(?:\[if\s|<!|>))(?:(?!-->).)*.?-->/s', '', $html);

        // Remove extra whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Remove leading/trailing whitespace from lines
        $html = preg_replace('/^\s+|\s+$/m', '', $html);

        // Collapse multiple whitespace characters into single space
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }

    /**
     * Aggressive HTML minification
     *
     * @param string $html HTML content
     * @return string Minified HTML
     */
    private static function aggressiveMinify(string $html): string
    {
        $html = self::basicMinify($html);

        // Remove whitespace around block elements
        $blockElements = 'div|p|h[1-6]|ul|ol|li|table|thead|tbody|tr|td|th|form|fieldset|nav|header|footer|section|article|aside|main';
        $html = preg_replace('/\s*(<(?:\/?' . $blockElements . ')[^>]*>)\s*/', '$1', $html);

        // Remove empty attributes (but preserve required ones)
        $html = preg_replace('/\s(class|id|style)=""\s*/', ' ', $html);

        // Remove unnecessary quotes from attributes (simple values only)
        $html = preg_replace('/\s([a-zA-Z-]+)="([a-zA-Z0-9-_\.]+)"\s*/', ' $1=$2 ', $html);

        return $html;
    }

    /**
     * Extreme HTML minification
     *
     * @param string $html HTML content
     * @return string Minified HTML
     */
    private static function extremeMinify(string $html): string
    {
        $html = self::aggressiveMinify($html);

        // Remove all newlines and extra spaces
        $html = str_replace(["\r\n", "\r", "\n", "\t"], '', $html);

        // Remove spaces around = in attributes
        $html = preg_replace('/\s*=\s*/', '=', $html);

        // Remove trailing spaces before >
        $html = preg_replace('/\s+>/', '>', $html);

        // Remove spaces after <
        $html = preg_replace('/<\s+/', '<', $html);

        return $html;
    }

    /**
     * Apply gzip compression
     *
     * @param string $content Content to compress
     * @return string Compressed content
     */
    private static function gzipCompress(string $content): string
    {
        // Set appropriate headers for gzip compression
        if (!headers_sent()) {
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
        }

        return gzencode($content, 9); // Maximum compression level
    }

    /**
     * Check if client supports gzip compression
     *
     * @return bool
     */
    private static function supportsGzip(): bool
    {
        return function_exists('gzencode') &&
               isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
               strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
    }

    /**
     * Compress JSON response
     *
     * @param array $data Data to JSON encode and compress
     * @return string Compressed JSON
     */
    public static function compressJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        if (self::$useGzip && self::supportsGzip()) {
            return self::gzipCompress($json);
        }

        return $json;
    }

    /**
     * Compress component content for SPA responses
     *
     * @param string $content Component HTML content
     * @return string Base64 encoded compressed content
     */
    public static function compressComponent(string $content): string
    {
        $compressed = self::compress($content);
        return base64_encode($compressed);
    }

    /**
     * Auto-detect best compression level based on content size
     *
     * @param string $content Content to analyze
     * @return int Recommended compression level
     */
    public static function detectOptimalLevel(string $content): int
    {
        $size = strlen($content);

        if ($size < 1024) { // Less than 1KB
            return self::LEVEL_BASIC;
        } elseif ($size < 10240) { // Less than 10KB
            return self::LEVEL_AGGRESSIVE;
        } else { // 10KB or more
            return self::LEVEL_EXTREME;
        }
    }
}
