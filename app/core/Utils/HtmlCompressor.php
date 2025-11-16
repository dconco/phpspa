<?php

namespace PhpSPA\Core\Utils;

use PhpSPA\Compression\Compressor;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for PhpSPA
 * to reduce payload sizes and improve performance.
 *
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 */
trait HtmlCompressor
{
    /**
     * Current compression level
     *
     * @var int
     */
    private static int $compressionLevel = 1; // Default to LEVEL_AUTO

    /**
     * Whether to use gzip compression
     *
     * @var bool
     */
    private static bool $useGzip = true;

    /**
     * Set compression level
     *
     * @param int $level Compression level (0-4)
     * @return void
     */
    public static function setLevel(int $level): void
    {
        self::$compressionLevel = max(0, min(4, $level));
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
    public static function compress(
        string $html,
        ?string $contentType = null,
    ): string {
        if (self::$compressionLevel === Compressor::LEVEL_NONE) {
            if (!headers_sent()) {
                header('Content-Length: ' . strlen($html));

                if ($contentType !== null) {
                    header("Content-Type: $contentType; charset=UTF-8");
                }
            }

            return $html;
        }

        $comment = "<!--
  🧩 PhpSPA Engine - Minified Output

  This HTML has been automatically minified by the PhpSPA runtime engine:
  • Whitespace removed for faster loading
  • Comments stripped (except this one)
  • Attributes optimized for minimal size
  • Performance-optimized for production

  Original source: Component-based PHP library with natural HTML syntax
  Learn More: https://phpspa.tech/v1.1.5/1-compression-system
-->\n";

        // Apply minification based on compression level
        $html = self::minify($html, self::$compressionLevel);

        // Append Comments
        $html = $comment . $html;

        // Apply gzip compression if enabled and supported
        $html = self::gzipCompress($html, $contentType);

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
        if ($level === Compressor::LEVEL_AUTO) {
            $level = self::detectOptimalLevel($html);
        }

        // Try to use binary compressor first
        if (self::hasBinaryCompressor()) {
            return self::compressWithBinary($html, $level);
        }

        // Fallback to PHP implementation
        return self::compressWithFallback($html, $level);
    }

    /**
     * Check if binary compressor is available
     *
     * @return bool
     */
    private static function hasBinaryCompressor(): bool
    {
        $binDir = dirname(__DIR__, 3) . '/src/bin';
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $binaryPath = realpath($binDir . '/compressor' . ($isWindows ? '.exe' : ''));
        
        return file_exists($binaryPath) && is_executable($binaryPath);
    }


    /**
     * Compress using binary compressor
     *
     * @param string $html HTML content
     * @param int $level Compression level
     * @return string Compressed HTML
     */
    private static function compressWithBinary(string $html, int $level): string
    {
        // $binDir = dirname(__DIR__, 3) . '/src/bin'; // --- PRODUCTION MODE ---
        $binDir = dirname(__DIR__, 3) . '/build/MinSizeRel'; // --- DEVELOPMENT MODE ---
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $binaryPath = realpath($binDir . '/compressor' . ($isWindows ? '.exe' : ''));

        $levelMap = [
            Compressor::LEVEL_BASIC => 1,
            Compressor::LEVEL_AGGRESSIVE => 2,
            Compressor::LEVEL_EXTREME => 3,
        ];

        $binaryLevel = $levelMap[$level] ?? 1;

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $command = escapeshellarg($binaryPath) . ' --level ' . $binaryLevel . ' --content w';
        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return self::compressWithFallback($html, $level); // Fallback if process fails
        }

        // Write HTML to stdin
        fwrite($pipes[0], $html);
        fclose($pipes[0]);

        // Read compressed output from stdout
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read any errors
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        // Return compressed output or fallback to original
        return ($returnCode === 0 && !empty($output)) ? trim($output) : self::compressWithFallback($html, $level);
    }


    /**
     * Compress using PHP fallback
     *
     * @param string $html HTML content
     * @param int $level Compression level
     * @return string Compressed HTML
     */
    private static function compressWithFallback(string $html, int $level): string
    {
        return match ($level) {
            Compressor::LEVEL_BASIC => FallbackCompressor::basicMinify($html),
            Compressor::LEVEL_AGGRESSIVE => FallbackCompressor::aggressiveMinify($html),
            Compressor::LEVEL_EXTREME => FallbackCompressor::extremeMinify($html),
            default => $html,
        };
    }



    /**
     * Apply gzip compression
     *
     * @param string $content Content to compress
     * @return string Compressed content
     */
    private static function gzipCompress(
        string $content,
        ?string $contentType,
    ): string {
        if (self::supportsGzip() && self::$useGzip) {
            $compressed = gzencode($content, 9); // Maximum compression level

            // Set appropriate headers for gzip compression
            if (!headers_sent()) {
                header('Content-Encoding: gzip');
                header('Vary: Accept-Encoding');
                header('Content-Length: ' . strlen($compressed));

                if ($contentType !== null) {
                    header("Content-Type: $contentType; charset=UTF-8");
                }
            }

            return $compressed;
        }

        if (!headers_sent()) {
            header('Content-Length: ' . strlen($content));

            if ($contentType !== null) {
                header("Content-Type: $contentType; charset=UTF-8");
            }
        }
        return $content;
    }

    /**
     * Check if client supports gzip compression
     *
     * @return bool
     */
    public static function supportsGzip(): bool
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
        $json = json_encode($data);
        return self::gzipCompress($json, 'application/json');
    }

    /**
     * Compress component content for SPA responses
     *
     * @param string $content Component HTML content
     * @return string Base64 encoded compressed content
     */
    public static function compressComponent(string $content): string
    {
        // Apply minification based on compression level
        return self::minify($content, Compressor::LEVEL_EXTREME);
    }



    /**
     * Auto-detect best compression level based on content size
     *
     * @param string $content Content to analyze
     * @return int Recommended compression level
     */
    private static function detectOptimalLevel(string $content): int
    {
        $size = strlen($content);

        if ($size < 1024) {
            // Less than 1KB
            return Compressor::LEVEL_BASIC;
        } elseif ($size < 10240) {
            // Less than 10KB
            return Compressor::LEVEL_AGGRESSIVE;
        } else {
            // 10KB or more
            return Compressor::LEVEL_EXTREME;
        }
    }

    /**
     * Get current compression level
     *
     * @return int Current compression level
     */
    public static function getLevel(): int
    {
        return self::$compressionLevel;
    }

    /**
     * Compress content with specific level
     *
     * @param string $content Content to compress
     * @param int $level Compression level
     * @return string Compressed content
     */
    public static function compressWithLevel(string $content, int $level): string
    {
        $originalLevel = self::$compressionLevel;
        self::$compressionLevel = $level;

        $compressed = self::minify($content, $level);

        self::$compressionLevel = $originalLevel;
        return $compressed;
    }
}
