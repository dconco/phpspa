<?php

declare(strict_types=1);

namespace PhpSPA\Core\Compression;

/**
 * Shared contract for the native (FFI-backed) minification engines.
 *
 * Both {@see CppCompressor} (the in-process C++ compressor, which also hosts the
 * esbuild path) and {@see OxcCompressor} (the in-process Oxc/Rust JS minifier)
 * implement this interface. {@see \PhpSPA\Core\Utils\HtmlCompressor} is the single
 * base that decides which concrete native engine to use per request, then calls
 * it polymorphically — e.g.:
 *
 *   $compressor = OxcCompressor::isAvailable() ? OxcCompressor::class : CppCompressor::class;
 *   $compressor::compress($content, $level, $type, $scope, $useEsbuild, $logs);
 *
 * The signature is unified across engines; an engine ignores the parameters it
 * does not need (Oxc, for instance, is JS-only and single-level, so it uses only
 * $content and $scope).
 *
 * @since v2.1.0
 */
interface NativeCompressor
{
   /**
    * Whether this native engine is loaded and usable right now.
    */
   public static function isAvailable(): bool;

   /**
    * The last initialization/availability error, or null if none.
    */
   public static function getLastError(): ?string;

   /**
    * The path to the native library file backing this engine, or null if unknown.
    */
   public static function getLibraryPath(): ?string;

   /**
    * Compress/minify content with this native engine.
    *
    * @param string $content     Content payload to compress
    * @param int    $nativeLevel Native compressor level (1-3); engines that are
    *                            single-level (Oxc) ignore it
    * @param string $type        Content type enum['HTML', 'JS', 'CSS']
    * @param string $scope       Compression scope enum['global', 'scoped']
    * @param bool   $useEsbuild  Use esbuild for JS minification (C++ engine only;
    *                            ignored by engines that are not esbuild-backed)
    * @param int    $disableLogs Log-suppression bit flags
    * @return string Compressed content
    *
    * @throws \RuntimeException on a genuine runtime FFI failure (after the engine
    *                           was confirmed available via {@see isAvailable()}).
    */
   public static function compress(
      string $content,
      int $nativeLevel,
      string $type,
      string $scope,
      bool $useEsbuild,
      int $disableLogs = 0
   ): string;
}
