<?php

namespace PhpSPA\Core\Utils;

use function strlen;
use function is_string;
use RuntimeException;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Compression\NativeCompressor;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for PhpSPA
 * to reduce payload sizes and improve performance.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
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
    * Tracks which engine handled the last compression call.
    */
   private static string $compressionEngine = 'php';

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
   public static function compress(string $html, ?string $contentType = null): string {
      if (self::$compressionLevel === Compressor::LEVEL_NONE) {
         self::setCompressionEngine('disabled');
         self::emitEngineHeader();

         if (!headers_sent()) {
            if ($contentType !== null) header("Content-Type: $contentType; charset=UTF-8");
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
  Learn More: https://phpspa.tech/performance/html-compression
-->\n";

      // Apply minification based on compression level
      $html = self::minify($html, 'HTML', self::$compressionLevel);

      // Append Comments
      $html = $comment . $html;

      // Apply binary compression (Zstd, Brotli, or Gzip) if enabled and supported
      $html = self::applyBinaryCompression($html, $contentType);

      return $html;
   }

   /**
    * Minify HTML content
    *
    * @param string $content HTML content
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param int $level Compression level
    * @param string $scope Compression scope enum['GLOBAL', 'SCOPED']
    * @return string Minified HTML
    */
   private static function minify(string $content, $type, int $level, string $scope = 'global', bool $useEsbuild = false): string
   {
      if ($level === Compressor::LEVEL_NONE) return $content;

      $preservedBlocks = null;
      if ($type === 'HTML') {
         [$content, $preservedBlocks] = self::protectPreformattedBlocks($content);
      }

      if ($level === Compressor::LEVEL_AUTO) {
         $level = self::detectOptimalLevel($content);
      }

      if (self::isNativeCompressorAvailable()) {
         $result = self::compressWithNative($content, $level, $type, $scope, $useEsbuild);
      } else {
         // Fallback to PHP implementation
         $result = self::compressWithFallback($content, $level, $type, $scope);
      }

      if ($type === 'HTML' && \is_array($preservedBlocks) && $preservedBlocks !== []) {
         $result = self::restorePreformattedBlocks($result, $preservedBlocks);
      }

      return $result;
   }

   /**
    * Protect preformatted blocks so minifiers don't alter whitespace/newlines.
    *
    * This prevents regex-based minification from collapsing whitespace inside tags
    * where whitespace is semantically important.
    *
    * @return array{0:string,1:array<string,string>} [htmlWithPlaceholders, placeholderMap]
    */
   private static function protectPreformattedBlocks(string $html): array
   {
      $placeholderMap = [];
      $index = 0;
      $pattern = '~<(pre|textarea|code|xmp)(?:\s[^>]*)?>.*?</\\1>~is';

      $protected = preg_replace_callback(
         $pattern,
         static function (array $matches) use (&$placeholderMap, &$index): string {
            $key = '__PHPSPA_PRESERVE_BLOCK_' . $index . '__';
            $placeholderMap[$key] = $matches[0];
            $index++;
            return $key;
         },
         $html,
      );

      return [is_string($protected) ? $protected : $html, $placeholderMap];
   }

   /**
    * Restore blocks previously protected by protectPreformattedBlocks().
    */
   private static function restorePreformattedBlocks(string $html, array $placeholderMap): string
   {
      return $placeholderMap === [] ? $html : strtr($html, $placeholderMap);
   }

   private static function isNativeCompressorAvailable(): bool
   {
      $strategy = self::compressionStrategy();

      if ($strategy !== 'fallback') {
         if (NativeCompressor::isAvailable()) {
               try {
                  self::setCompressionEngine('native');
                  self::emitEngineHeader();
                  return true;
               } catch (\Throwable $exception) {
                  if ($strategy === 'native') {
                     $details = NativeCompressor::getLastError();
                     $suffix = $details ? ' (' . $details . ')' : '';
                     throw new RuntimeException('Native compressor is required but failed to execute' . $suffix . '.', 0, $exception);
                  }
               };
         } elseif ($strategy === 'native') {
            $details = NativeCompressor::getLastError();
            $suffix = $details ? ' (' . $details . ')' : '';
            throw new RuntimeException('Native compressor is required but unavailable' . $suffix . '.');
         }
      }

      self::setCompressionEngine('php');
      self::emitEngineHeader();
      return false;
   }

   /**
    * Compress using the native shared library via FFI
    */
   private static function compressWithNative(string $html, int $level, string $type, string $scope, bool $useEsbuild): string
   {
		$nativeLevel = match ($level) {
         Compressor::LEVEL_AGGRESSIVE => 2,
         Compressor::LEVEL_EXTREME => 3,
         default => 1,
      };

      return NativeCompressor::compress($html, $nativeLevel, $type, $scope, $useEsbuild);
   }

   /**
    * Compress using PHP fallback
    *
    * @param string $content HTML content
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param int $level Compression level
    * @return string Compressed HTML
    */
   private static function compressWithFallback(string $content, int $level, string $type, string $scope = 'global'): string
   {
      if ($type === 'JS') $content = "<script>$content</script>";
      elseif ($type === 'CSS') $content = "<style>$content</style>";

      $result = match ($level) {
         Compressor::LEVEL_BASIC => FallbackCompressor::basicMinify($content),
         Compressor::LEVEL_AGGRESSIVE => FallbackCompressor::aggressiveMinify($content),
         Compressor::LEVEL_EXTREME => FallbackCompressor::extremeMinify($content),
         default => $content,
      };
      $result = trim($result);

      if ($type === 'JS') {
         $result = substr($result, 8, -9); // Extract content inside <script> tags

         if ($scope === 'scoped' && !empty($result)) {
            $result = rtrim($result, "; \t\n\r\0\x0B");
            $result = "(()=>{{$result};})();";
         }
      }
      elseif ($type === 'CSS') $result = substr($result, 7, -8); // Extract content inside <style> tags

      return $result;
   }

   private static function compressionStrategy(): string
   {
      static $strategy = null;

      if ($strategy !== null) {
         return $strategy;
      }

      $envStrategy = getenv('PHPSPA_COMPRESSION_STRATEGY');
      $normalized = is_string($envStrategy)
         ? strtolower(trim($envStrategy))
         : '';

      if ($normalized === 'native' || $normalized === 'fallback') {
         return $strategy = $normalized;
      }

      return $strategy = 'auto';
   }

   private static function emitEngineHeader(): void
   {
      if (PHP_SAPI === 'cli' || headers_sent()) {
         return;
      }

      header('X-PhpSPA-Compression-Engine: ' . self::$compressionEngine);
   }

   private static function setCompressionEngine(string $engine): void
   {
      self::$compressionEngine = $engine;
   }


    /**
     * Apply binary compression based on content negotiation and server support.
     * Tiered priority: Zstd > Brotli > Gzip
     *
     * @param string $content Content to compress
     * @param string|null $contentType Optional content type header
     * @return string Compressed content
     */
   public static function applyBinaryCompression(
      string $content,
      ?string $contentType = null,
   ): string {
      if (!self::$useGzip) {
         if (!headers_sent() && $contentType !== null) {
            header("Content-Type: $contentType; charset=UTF-8");
         }
         return $content;
      }

      $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
      $compressed = null;
      $encoding = null;

      // 1. Check Zstd (Priority 1)
      if (function_exists('\zstd_compress') && strpos($acceptEncoding, 'zstd') !== false) {
         $compressed = \zstd_compress($content, 3); // Recommended level for web
         $encoding = 'zstd';
      }
      // 2. Check Brotli (Priority 2)
      elseif (function_exists('\brotli_compress') && strpos($acceptEncoding, 'br') !== false) {
         $compressed = \brotli_compress($content, 4, \BROTLI_TEXT);
         $encoding = 'br';
      }
      // 3. Fallback to Gzip (Priority 3)
      elseif (function_exists('\gzencode') && strpos($acceptEncoding, 'gzip') !== false) {
         $compressed = \gzencode($content, 9);
         $encoding = 'gzip';
      }

      if ($encoding !== null && $compressed !== false && $compressed !== null) {
         if (!headers_sent()) {
            header("Content-Encoding: $encoding");
            header('Vary: Accept-Encoding');
            header('Content-Length: ' . strlen($compressed));

            if ($contentType !== null) {
               header("Content-Type: $contentType; charset=UTF-8");
            }
         }

         return $compressed;
      }

      if (!headers_sent() && $contentType !== null) {
         header("Content-Type: $contentType; charset=UTF-8");
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
      return self::applyBinaryCompression($json, 'application/json');
   }

   /**
    * Compress component content for SPA responses
    *
    * @param string $content Component HTML content
    * @param string $type Content type enum['HTML', 'JS', 'CSS'] 
    * @return string Base64 encoded compressed content
    */
   public static function compressComponent(string $content, string $type = 'HTML'): string
   {
      if (self::getLevel() === Compressor::LEVEL_NONE) return $content;

      // Apply minification based on compression level
      return self::minify($content, $type, Compressor::LEVEL_EXTREME);
   }



   /**
    * Auto-detect best compression level based on content size
    *
    * @param string $content Content to analyze
    * @return int Recommended compression level
    */
   private static function detectOptimalLevel(string $content): int
   {
      $contentLen = strlen($content);

      if ($contentLen < 1024) {
         // Less than 1KB
         return Compressor::LEVEL_BASIC;
      } elseif ($contentLen < 10240) {
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
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param string $scope Compression scope enum['GLOBAL', 'SCOPED']
    * @param bool $useEsbuild Use esbuild for minification
    * @return string Compressed content
    */
   public static function compressWithLevel(string $content, int $level, string $type = 'HTML', string $scope = 'global', bool $useEsbuild = false): string
   {
      return self::minify($content, $type, $level, $scope, $useEsbuild);
   }

   public static function getCompressionEngine(): string
   {
      return self::$compressionEngine;
   }
}
