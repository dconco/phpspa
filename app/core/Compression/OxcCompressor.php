<?php

declare(strict_types=1);

namespace PhpSPA\Core\Compression;

/**
 * In-process JavaScript minifier backed by the Oxc (Rust) shared library,
 * called over FFI.
 *
 * Oxc is **opt-in** (`useOxcForJsMinification()`) and loads one of two ways:
 *   1. Preloaded under the `phpspa_oxc` FFI scope via `ffi.preload` (preferred —
 *      the library is already in shared OPcache memory, zero per-request load).
 *   2. Dynamically via `FFI::cdef` from the library path set with
 *      `setCustomOxcLibraryPath()` (or auto-resolved from the build dirs) when it
 *      is not preloaded.
 *
 * Fallback policy (decided by the caller, {@see \PhpSPA\Core\Utils\HtmlCompressor}):
 *   - Oxc available              -> used.
 *   - Oxc unavailable + forced   -> throw (forceNativeCompression / strategy=native).
 *   - Oxc unavailable otherwise  -> degrade to the PHP minifier.
 * Oxc must NEVER fall back to the C++ minifier — that is esbuild's behaviour, not
 * Oxc's. Availability is probed with {@see isAvailable()} before {@see compress()};
 * `compress()` only throws on a genuine runtime failure (null pointer) after the
 * library was confirmed available.
 *
 * Error contract (mirrors `oxc/lib.rs`):
 *   - A genuine FFI/allocation failure → null pointer → this class throws.
 *   - The developer's own broken JS → Oxc returns `__OXC_ERROR__<message>`,
 *     which is passed through verbatim as the JS body so the error surfaces in
 *     the browser console while the page still serves. No exception, no fallback.
 *
 * @since v2.1.0
 */
final class OxcCompressor implements NativeCompressor
{
   /**
    * FFI scope name the Oxc library is preloaded under in the PHP INI, i.e. the
    * `FFI_SCOPE` in the preload header (`phpspa_oxc.h`).
    */
   private const string FFI_SCOPE = 'phpspa_oxc';

   /**
    * Env var pointing at the Oxc shared library file. Set via
    * `setCustomOxcLibraryPath()`. Used when the library is NOT preloaded: Oxc is
    * then loaded dynamically with `FFI::cdef` from this path (or an auto-resolved
    * build path), mirroring {@see CppCompressor}.
    */
   private const string ENV_LIBRARY_PATH = 'PHPSPA_OXC_LIB';

   /**
    * Sentinel prefix the Rust side uses to report a parse error as its result
    * string. Must match `ERROR_PREFIX` in `oxc/lib.rs`.
    */
   private const string ERROR_PREFIX = '__OXC_ERROR__';

   private static ?bool $available = null;

   private static ?string $lastError = null;

   private static ?\FFI $ffi = null;

   private static ?string $libraryPath = null;

   /**
    * Whether the Oxc library is usable — either preloaded under the `phpspa_oxc`
    * FFI scope, or dynamically loadable via `FFI::cdef` from a configured/auto
    * path ({@see setCustomOxcLibraryPath}).
    */
   public static function isAvailable(): bool
   {
      if (self::$available !== null) return self::$available;

      return self::$available = self::initialize(DISABLE_LIBRARY_LOG | DISABLE_PRELOAD_LOG);
   }

   public static function getLastError(): ?string
   {
      return self::$lastError;
   }

   /**
    * Minify JavaScript using the Oxc shared library.
    *
    * Implements the shared {@see NativeCompressor} signature. Oxc is JS-only, so
    * `$type` and `$useEsbuild` are accepted for interface compatibility but
    * ignored. `$nativeLevel` and `$scope` BOTH drive the minification:
    *
    *   level 1 (BASIC)      — baseline minify; global keeps all names, scoped
    *                          mangles + IIFE. Comments left to codegen.
    *   level 2 (AGGRESSIVE) — BASIC + strip ALL comments.
    *   level 3 (EXTREME)    — strip ALL comments + mangle hardest Oxc allows:
    *                          global keeps TOP-LEVEL names but mangles inner
    *                          scopes (UMD `exports`/`module` reserved); scoped
    *                          mangles everything.
    *
    * `$disableLogs` is honoured (it gates the FFI load log lines).
    *
    * @param string $content     JavaScript source
    * @param int    $nativeLevel Minify level: 1=basic, 2=aggressive, 3=extreme
    * @param string $type        Ignored (Oxc is JS-only; HtmlCompressor guarantees JS)
    * @param string $scope       Compression scope enum['global', 'scoped']
    *                            - 'global' keeps top-level names
    *                            - 'scoped' mangles + wraps in an IIFE
    * @param bool   $useEsbuild  Ignored (Oxc is not esbuild-backed)
    * @param int    $disableLogs Log-suppression bit flags for the FFI load
    * @return string Minified JS, or Oxc's error body verbatim on a parse error
    *                (passed through, not thrown).
    * @throws \RuntimeException on a genuine runtime FFI failure (null pointer).
    *                Call {@see isAvailable()} first — this assumes the library is
    *                loadable and does not itself decide PHP-fallback.
    */
   public static function compress(string $content, int $nativeLevel, string $type, string $scope, bool $useEsbuild, int $disableLogs = 0): string
   {
      if (!self::initialize($disableLogs)) {
         throw new \RuntimeException(self::$lastError ?? 'Oxc minifier is unavailable.');
      }

      // Oxc expects a lowercase scope ('global' | 'scoped'); normalise so an
      // upstream 'GLOBAL'/'SCOPED' still selects the right branch.
      $oxcScope = \strtolower($scope) === 'scoped' ? 'scoped' : 'global';

      $resultPointer = self::invoke('oxc_minify', $content, $oxcScope, $nativeLevel);

      // Null == hard FFI/allocation failure → throw (no fallback). A parse error
      // is NOT null; it comes back as a `__OXC_ERROR__…` string handled below.
      if ($resultPointer === null || \FFI::isNull($resultPointer)) {
         throw new \RuntimeException('Oxc minifier returned a null pointer.');
      }

      try {
         // Pass Oxc's output through verbatim — including an `__OXC_ERROR__…`
         // body, which the asset layer serves so the dev sees the parse error.
         return \FFI::string($resultPointer);
      } finally {
         self::invoke('oxc_free_string', $resultPointer);
      }
   }

   /**
    * Resolve the Oxc FFI handle. Tries the preloaded `phpspa_oxc` scope first
    * (fastest — library already in shared OPcache memory); if not preloaded,
    * falls back to a dynamic `FFI::cdef` load from the configured/auto-resolved
    * library path, mirroring {@see CppCompressor}.
    */
   private static function initialize(int $disableLogs = 0): bool
   {
      if (self::$ffi !== null) {
         return true;
      }

      self::$lastError = null;

      if (!\extension_loaded('FFI')) {
         self::$lastError = 'FFI extension is not loaded.';
         return false;
      }

      // 1) Preferred: already preloaded into shared OPcache memory.
      try {
         self::$ffi = \FFI::scope(self::FFI_SCOPE);
         return true;
      } catch (\FFI\Exception $e) {
         if (!($disableLogs & DISABLE_PRELOAD_LOG)) error_log("phpspa_oxc isn't preloaded in PHP INI, falling back to loading directly from the shared library.");
         // Not preloaded; fall through to dynamic loading below.
      }

      // 2) Dynamic load via FFI::cdef from the configured path.
      $libraryPath = self::resolveLibraryPath($disableLogs);
      if ($libraryPath === null) {
         $env = self::ENV_LIBRARY_PATH;
         self::$lastError =
            "Oxc minifier is not preloaded and no library was found. Either add "
            . "the Oxc header to ffi.preload (FFI_SCOPE=\"" . self::FFI_SCOPE . "\"), "
            . "or set {$env} (via setCustomOxcLibraryPath()) to the Oxc shared library.";
         return false;
      }

      try {
         self::$ffi = \FFI::cdef(self::cDefinition(), $libraryPath);
         self::$libraryPath = $libraryPath;
         if (!($disableLogs & DISABLE_LIBRARY_LOG)) error_log('Loaded Oxc minifier library: ' . $libraryPath);
         return true;
      } catch (\Throwable $e) {
         self::$ffi = null;
         self::$lastError = 'FFI failed to load Oxc library ' . $libraryPath . ': ' . $e->getMessage();
         return false;
      }
   }

   public static function getLibraryPath(): ?string
   {
      return self::$libraryPath;
   }

   /**
    * Resolve the Oxc shared library path for dynamic loading. The Oxc library is
    * NOT shipped with phpspa (unlike the C++ compressor) — the user downloads it
    * from the release and points at it with `setCustomOxcLibraryPath()`. So this
    * uses ONLY the configured {@see ENV_LIBRARY_PATH}; there is no build-dir scan.
    */
   private static function resolveLibraryPath(int $disableLogs = 0): ?string
   {
      $envPath = \getenv(self::ENV_LIBRARY_PATH);
      if (!\is_string($envPath) || $envPath === '') {
         $envPath = $_ENV[self::ENV_LIBRARY_PATH] ?? '';
      }
      if (\is_string($envPath) && $envPath !== '' && \is_file($envPath)) {
         if (!($disableLogs & DISABLE_LIBRARY_LOG)) error_log("Using Oxc minifier library from environment variable: $envPath");
         return $envPath;
      }

      return null;
   }

   /**
    * C ABI the dynamic `FFI::cdef` load declares. Must match `oxc/lib.rs` and
    * the preload header `examples/phpspa_oxc.h`.
    */
   private static function cDefinition(): string
   {
      return <<<'CDEF'
char* oxc_minify(const char* source, const char* scope, int level);
void oxc_free_string(char* ptr);
CDEF;
   }

   private static function invoke(string $symbol, mixed ...$arguments): mixed
   {
      if (self::$ffi === null) {
         throw new \RuntimeException('Oxc minifier is not initialized.');
      }

      $callable = [self::$ffi, $symbol];
      return $callable(...$arguments);
   }
}
