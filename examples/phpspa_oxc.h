// PHP.INI CONFIGURATION
// ffi.enable = "preload"
// ffi.preload = "/absolute/path/to/this/file/phpspa_oxc.h"
// opcache.enable = 1
//
// The Oxc JS minifier is OPT-IN. It is used only when the app calls
// $app->useOxcForJsMinification(). It loads one of two ways:
//   1. Preloaded under this FFI_SCOPE via ffi.preload (preferred — the library
//      is already in shared OPcache memory, no per-request load).
//   2. If NOT preloaded, dynamically via FFI::cdef from the path set with
//      $app->setCustomOxcLibraryPath('/abs/path/to/lib') (or PHPSPA_OXC_LIB).
// When Oxc is enabled but neither is available it degrades to the PHP minifier
// (never C++); forceNativeCompression() makes it throw instead.
//
// Download the Oxc shared library for your platform from the GitHub release
// assets (it is NOT committed to src/bin):
//   Linux   -> liboxc_php_minifier.so
//   Windows -> oxc_php_minifier.dll
//   macOS   -> liboxc_php_minifier.dylib

// path to the Oxc minifier library
#define FFI_LIB "/path/to/liboxc_php_minifier.so"
#define FFI_SCOPE "phpspa_oxc"

// Minify `source` under `scope` and `level`.
//   scope: "global" keeps top-level identifier names; "scoped" mangles + wraps
//          the code in an IIFE (mirrors the esbuild contract).
//   level: 1 = basic (baseline minify), 2 = aggressive (basic + strip all
//          comments), 3 = extreme (strip comments + mangle hardest Oxc allows;
//          global still keeps top-level names but mangles inner scopes).
// Returns a heap C-string that MUST be freed with oxc_free_string().
// A null return is a hard FFI/allocation failure (phpspa throws); a
// "__OXC_ERROR__<message>" return is the dev's invalid JS, served verbatim.
char* oxc_minify(const char* source, const char* scope, int level);
void oxc_free_string(char* ptr);
