<?php

declare(strict_types=1);

namespace PhpSPA\Core\Compression;

final class NativeCompressor
{
   private const ENV_LIBRARY_PATH = 'PHPSPA_COMPRESSOR_LIB';

   private static ?bool $available = null;

   private static ?string $lastError = null;

   private static ?\FFI $ffi = null;

   private static ?string $libraryPath = null;

   public static function isAvailable(): bool
   {
      if (self::$available !== null) return self::$available;

      return self::$available = self::initialize();
   }

   public static function getLastError(): ?string
   {
      return self::$lastError;
   }

   /**
    * Compress HTML using the native shared library.
    *
    * @param string $content Content payload to compress
    * @param int $nativeLevel Native compressor level (1-3)
    * @param string $type Content type enum['HTML', 'JS', 'CSS']
    * @param ?string $scoped Decides if the JS to compress is scoped or global
    * @return string
    */
   public static function compress(string $content, int $nativeLevel, string $type, ?string $scoped): string
   {
      if (!self::initialize()) {
         throw new \RuntimeException('Native compressor is unavailable.');
      }

      $level = max(1, min(3, $nativeLevel));
      $outLen = self::$ffi->new('size_t');

      $resultPointer = self::invoke('phpspa_compress_html_ex', $content, $level, $type, $scoped, \FFI::addr($outLen));

      if ($resultPointer === null || \FFI::isNull($resultPointer)) {
         throw new \RuntimeException('Native compressor returned a null pointer.');
      }

      try {
         return \FFI::string($resultPointer, $outLen->cdata);
      } finally {
         self::invoke('phpspa_free_string', $resultPointer);
      }
   }

   public static function getLibraryPath(): ?string
   {
      return self::$libraryPath;
   }

   private static function initialize(): bool
   {
      if (self::$ffi !== null) {
         return true;
      }

      self::$lastError = null;

      if (!\extension_loaded('FFI')) {
         self::$lastError = 'FFI extension is not loaded.';
         return false;
      }

      $libraryPath = self::resolveLibraryPath();
      if ($libraryPath === null) {
         self::$lastError = 'Native compressor library not found. Set PHPSPA_COMPRESSOR_LIB to an absolute path to compressor.dll.';
         return false;
      }

      try {
         self::$ffi = \FFI::cdef(self::cDefinition(), $libraryPath);
         self::$libraryPath = $libraryPath;
         return true;
      } catch (\Throwable $e) {
         self::$ffi = null;
         self::$lastError = 'FFI failed to load ' . $libraryPath . ': ' . $e->getMessage();
         return false;
      }
   }

   private static function resolveLibraryPath(): ?string
   {
      $envPath = \getenv(self::ENV_LIBRARY_PATH);
      if (\is_string($envPath) && $envPath !== '' && \is_file($envPath)) {
         return $envPath;
      }

      $baseDir = \dirname(__DIR__, 3);
      $directories = [
         $baseDir . '/src/bin',
         $baseDir . '/build/MinSizeRel',
         $baseDir . '/build/Release',
         $baseDir . '/build/RelWithDebInfo',
         $baseDir . '/build/Debug',
         $baseDir . '/build',
      ];

      foreach ($directories as $directory) {
         foreach (self::libraryFilenames() as $filename) {
            $candidate = $directory . '/' . $filename;
            if (\is_file($candidate)) {
               return $candidate;
            }
         }
      }

      return null;
   }

   private static function invoke(string $symbol, mixed ...$arguments): mixed
   {
      if (self::$ffi === null) {
         throw new \RuntimeException('Native compressor is not initialized.');
      }

      $callable = [self::$ffi, $symbol];
      return $callable(...$arguments);
   }

   private static function libraryFilenames(): array
   {
      return match (\PHP_OS_FAMILY) {
         'Windows' => ['compressor.dll'],
         'Darwin' => ['libcompressor.dylib', 'compressor.dylib'],
         default => ['libcompressor.so', 'compressor.so'],
      };
   }

   private static function cDefinition(): string
   {
      return <<<'CDEF'
char* phpspa_compress_html(const char* input, int level, const char* type, size_t* out_len);
char* phpspa_compress_html_ex(const char* input, int level, const char* type, const char* scope, size_t* out_len);
void phpspa_free_string(char* buffer);
CDEF;
   }
}
