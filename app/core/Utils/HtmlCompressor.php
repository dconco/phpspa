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
      $CONTENT_LENGTH = strlen($html);

      if (self::$compressionLevel === Compressor::LEVEL_NONE) {
         self::setCompressionEngine('disabled');
         self::emitEngineHeader();

         if (!headers_sent()) {
            header("Content-Length: $CONTENT_LENGTH");
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
      $html = self::minify($html, 'HTML', self::$compressionLevel, $CONTENT_LENGTH);

      // Append Comments
      $html = $comment . $html;

      // Apply gzip compression if enabled and supported
      $html = self::gzipCompress($html, $contentType);

      return $html;
   }

   /**
    * Minify HTML content
    *
    * @param string $content HTML content
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param int $level Compression level
    * @return string Minified HTML
    */
   private static function minify(string $content, $type, int $level, ?int $CONTENT_LENGTH = null): string
   {
      if ($level === Compressor::LEVEL_NONE) return $content;
      if (!$CONTENT_LENGTH) $CONTENT_LENGTH = strlen($content);

      $preservedBlocks = null;
      if ($type === 'HTML') {
         [$content, $preservedBlocks] = self::protectPreformattedBlocks($content);
         $CONTENT_LENGTH = strlen($content);
      }

      if ($level === Compressor::LEVEL_AUTO) {
         $level = self::detectOptimalLevel($content, $CONTENT_LENGTH);
      }

      if (self::isNativeCompressorAvailable($CONTENT_LENGTH)) {
         $result = self::compressWithNative($content, $level, $type);
      } else {
         // Fallback to PHP implementation
         $result = self::compressWithFallback($content, $level, $type);
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

   private static function isNativeCompressorAvailable(int $CONTENT_LENGTH): bool
   {
      static $THRESHOLD_SIZE = 10240; // 5KB = 5120 bytes

      $strategy = self::compressionStrategy();

      if ($strategy !== 'fallback') {
         if (NativeCompressor::isAvailable()) {
            // if ($CONTENT_LENGTH > $THRESHOLD_SIZE)
               try {
                  self::setCompressionEngine('native');
                  self::emitEngineHeader();
                  return true;
               } catch (\Throwable $exception) {
                  if ($strategy === 'native') 
                     throw new RuntimeException('Native compressor is required but failed to execute.', 0, $exception);
               };
         } elseif ($strategy === 'native')
            throw new RuntimeException('Native compressor is required but unavailable.');
      }

      self::setCompressionEngine('php');
      self::emitEngineHeader();
      return false;
   }

   /**
    * Compress using the native shared library via FFI
    */
   private static function compressWithNative(string $html, int $level, string $type): string
   {
		$nativeLevel = match ($level) {
         Compressor::LEVEL_AGGRESSIVE => 2,
         Compressor::LEVEL_EXTREME => 3,
         default => 1,
      };

      return NativeCompressor::compress($html, $nativeLevel, $type);
   }

   /**
    * Compress using PHP fallback
    *
    * @param string $content HTML content
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param int $level Compression level
    * @return string Compressed HTML
    */
   private static function compressWithFallback(string $content, int $level, string $type): string
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

      if ($type === 'JS') $result = substr($result, 8, -9); // Extract content inside <script> tags
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
    * Apply gzip compression
    *
    * @param string $content Content to compress
    * @return string Compressed content
    */
   public static function gzipCompress(
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
    * @param string $type Content type enum['HTML', 'JS', 'CSS'] 
    * @return string Base64 encoded compressed content
    */
   public static function compressComponent(string $content, $type = 'HTML'): string
   {
      $currentLevel = Compressor::getLevel();

      // Apply minification based on compression level
      return self::minify($content, $type, $currentLevel === Compressor::LEVEL_NONE ? $currentLevel : Compressor::LEVEL_EXTREME);
   }



   /**
    * Auto-detect best compression level based on content size
    *
    * @param string $content Content to analyze
    * @return int Recommended compression level
    */
   private static function detectOptimalLevel(string $content, ?int $CONTENT_LENGTH = null): int
   {
      if (!$CONTENT_LENGTH) $CONTENT_LENGTH = strlen($content);

      if ($CONTENT_LENGTH < 1024) {
         // Less than 1KB
         return Compressor::LEVEL_BASIC;
      } elseif ($CONTENT_LENGTH < 10240) {
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
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param int $level Compression level
    * @return string Compressed content
    */
   public static function compressWithLevel(string $content, int $level, $type = 'HTML'): string
   {
      return self::minify($content, $type, $level);
   }

   public static function getCompressionEngine(): string
   {
      return self::$compressionEngine;
   }
}
