<?php

namespace PhpSPA\Core\Utils;

use function strlen;
use function is_string;
use RuntimeException;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Compression\NativeCompressor;
use PhpSPA\Core\Compression\CppCompressor;
use PhpSPA\Core\Compression\OxcCompressor;

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
    * @param string $engine JS minification engine enum['native', 'esbuild', 'oxc']
    * @param int $disableLogs Flag to disable logging
    * @return string Minified HTML
    */
   private static function minify(string $content, $type, int $level, string $scope = 'global', string $engine = 'native', int $disableLogs = 0): string
   {
      if ($level === Compressor::LEVEL_NONE) return $content;

      // Oxc is a JS-only engine; for HTML/CSS (or when Oxc isn't the selected
      // engine) the request uses the C++/esbuild native path. Normalise here so
      // the single native decision below is engine-aware.
      $engine = ($engine === 'oxc' && $type !== 'JS') ? 'native' : $engine;

      $preservedBlocks = null;
      if ($type === 'HTML') {
         [$content, $preservedBlocks] = self::protectPreformattedBlocks($content);
      }

      if ($level === Compressor::LEVEL_AUTO) {
         $level = self::detectOptimalLevel($content);
      }

      // BASIC is a no-op for CSS and for C++-minified JS, so skip those cases
      // entirely instead of handing them to the native path (which would reach
      // the C++ library only to copy the input back verbatim):
      //
      //   - CSS at BASIC: both the C++ minifyCSS() and the PHP fallback bail out
      //     below AGGRESSIVE — CSS is never touched at BASIC by any engine, so
      //     short-circuit regardless of engine.
      //   - JS at BASIC: skip ONLY when the engine that would run is C++ (either
      //     the resolved native engine is C++, or the native path is unavailable
      //     and PHP fallback — also a BASIC JS no-op — would run). esbuild and an
      //     available Oxc DO perform real work at BASIC, so they are never skipped.
      //
      // Nothing is minified in the skipped cases, so the honest engine header is
      // 'disabled', matching LEVEL_NONE.
      if ($level === Compressor::LEVEL_BASIC && self::isBasicNoOp($type, $engine)) {
         self::setCompressionEngine('disabled');
         self::emitEngineHeader();

         if ($type === 'HTML' && \is_array($preservedBlocks) && $preservedBlocks !== []) {
            $content = self::restorePreformattedBlocks($content, $preservedBlocks);
         }

         return $content;
      }

      // Single base decision: is a native engine available for THIS engine choice?
      //   - available              -> compressWithNative() picks C++ / esbuild / Oxc.
      //   - unavailable + forced    -> isNativeCompressorAvailable() throws.
      //   - unavailable otherwise   -> PHP fallback.
      // Oxc never falls back to C++: when Oxc is the engine, the native path below
      // calls Oxc directly, and the PHP branch here is its only non-forced fallback.
      if (self::isNativeCompressorAvailable($engine)) {
         $result = self::compressWithNative($content, $level, $type, $scope, $engine, $disableLogs);
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

   /**
    * Pick the concrete native engine class for this engine choice.
    *
    * Both returned classes implement {@see NativeCompressor}. Oxc is selected
    * only when it is the requested engine AND actually available (preloaded);
    * everything else — including the esbuild path, which lives inside the C++
    * library — uses {@see CppCompressor}. This is the single point where
    * HtmlCompressor decides "which native".
    *
    * @param string $engine enum['native', 'esbuild', 'oxc']
    * @return class-string<NativeCompressor>
    */
   private static function resolveNativeCompressor(string $engine): string
   {
      if ($engine === 'oxc' && OxcCompressor::isAvailable()) {
         return OxcCompressor::class;
      }

      return CppCompressor::class;
   }

   /**
    * Whether compressing this content at LEVEL_BASIC would be a no-op, so the
    * request can skip the native/fallback path entirely.
    *
    *   - CSS: always a no-op at BASIC. Both the C++ minifyCSS() and the PHP
    *     fallback return early below AGGRESSIVE, so CSS is never minified at
    *     BASIC by any engine.
    *   - JS: a no-op at BASIC ONLY when the C++ engine would run it — i.e. the
    *     resolved native engine is C++ (the C++ minifyJS BASIC pass is the
    *     redundant work we skip), or the native path is unavailable so the PHP
    *     fallback (also a BASIC JS no-op) would run. esbuild and an available
    *     Oxc perform real work at BASIC and are therefore NOT skipped.
    *   - HTML and anything else: never skipped here (HTML whitespace collapse
    *     is meaningful at BASIC).
    *
    * @param string $type   Content type enum['HTML', 'JS', 'CSS']
    * @param string $engine Resolved engine enum['native', 'esbuild', 'oxc']
    */
   private static function isBasicNoOp(string $type, string $engine): bool
   {
      if ($type === 'CSS') {
         return true;
      }

      if ($type === 'JS') {
         // esbuild always does real BASIC work; Oxc does when it's available.
         // Everything else means the C++ (or PHP-fallback) path would run it —
         // a BASIC JS no-op worth skipping.
         if ($engine === 'esbuild') {
            return false;
         }
         if ($engine === 'oxc' && OxcCompressor::isAvailable()) {
            return false;
         }
         return true;
      }

      return false;
   }

   /**
    * Whether a native engine is available for this request's engine choice.
    *
    * Oxc never falls back to C++: when Oxc is the selected engine it is either
    * available (used), or — per strategy — this throws (forced) or returns false
    * so the caller degrades to the PHP minifier. The C++/esbuild path keeps its
    * existing behaviour.
    *
    * @param string $engine enum['native', 'esbuild', 'oxc']
    */
   private static function isNativeCompressorAvailable(string $engine = 'native'): bool
   {
      $strategy = self::compressionStrategy();

      // The concrete engine that would actually run. For 'oxc' this is
      // OxcCompressor only when it's available; otherwise CppCompressor — but a
      // selected-but-unavailable Oxc must NOT silently become C++, so handle it
      // explicitly here rather than letting the resolver swap it.
      if ($engine === 'oxc' && !OxcCompressor::isAvailable()) {
         if ($strategy === 'native') {
            $details = OxcCompressor::getLastError();
            $suffix = $details ? ' (' . $details . ')' : '';
            throw new RuntimeException('Oxc minifier is required but unavailable' . $suffix . '.');
         }

         // Not forced: degrade to PHP (NOT C++).
         self::setCompressionEngine('php');
         self::emitEngineHeader();
         return false;
      }

      $compressor = self::resolveNativeCompressor($engine);

      if ($strategy !== 'fallback') {
         if ($compressor::isAvailable()) {
               try {
                  self::setCompressionEngine('native');
                  self::emitEngineHeader();
                  return true;
               } catch (\Throwable $exception) {
                  if ($strategy === 'native') {
                     $details = $compressor::getLastError();
                     $suffix = $details ? ' (' . $details . ')' : '';
                     throw new RuntimeException('Native compressor is required but failed to execute' . $suffix . '.', 0, $exception);
                  }
               };
         } elseif ($strategy === 'native') {
            $details = $compressor::getLastError();
            $suffix = $details ? ' (' . $details . ')' : '';
            throw new RuntimeException('Native compressor is required but unavailable' . $suffix . '.');
         }
      }

      self::setCompressionEngine('php');
      self::emitEngineHeader();
      return false;
   }

   /**
    * Compress using a native engine via FFI. The concrete engine (C++ / esbuild
    * / Oxc) is chosen by {@see resolveNativeCompressor()} and invoked through the
    * shared {@see NativeCompressor} contract.
    *
    * @param string $engine enum['native', 'esbuild', 'oxc']
    */
   private static function compressWithNative(string $html, int $level, string $type, string $scope, string $engine, int $disableLogs): string
   {
		$nativeLevel = match ($level) {
         Compressor::LEVEL_AGGRESSIVE => 2,
         Compressor::LEVEL_EXTREME => 3,
         default => 1,
      };

      $compressor = self::resolveNativeCompressor($engine);
      $useEsbuild = ($engine === 'esbuild');

      return $compressor::compress($html, $nativeLevel, $type, $scope, $useEsbuild, $disableLogs);
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
      string &$content,
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
    * @param int $disableLogs Flag to disable logging
    * @return string Base64 encoded compressed content
    */
   public static function compressComponent(string $content, int $disableLogs = 0): string
   {
      if (self::getLevel() === Compressor::LEVEL_NONE) return $content;

      // Apply minification based on compression level
      return self::minify($content, 'HTML', Compressor::LEVEL_EXTREME, 'global', 'native', $disableLogs);
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
    * @param string $engine JS minification engine enum['native', 'esbuild', 'oxc']
    * @param int $disableLogs Flag to disable logging
    * @return string Compressed content
    */
   public static function compressWithLevel(string $content, int $level, string $type = 'HTML', string $scope = 'global', string $engine = 'native', int $disableLogs = 0): string
   {
      return self::minify($content, $type, $level, $scope, $engine, $disableLogs);
   }

   public static function getCompressionEngine(): string
   {
      return self::$compressionEngine;
   }
}
