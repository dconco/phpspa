# Performance: HTML Compression

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

To ensure your application is as fast as possible, PhpSPA includes a powerful, built-in HTML compressor. It automatically minifies your final HTML output by removing whitespace, comments, and other unnecessary characters, which reduces the page size and leads to faster load times. ⚡

!!! info "Auto-Detection"
    By default, PhpSPA tries to auto-detect the best settings. However, you can take full control for fine-tuned performance.

## Automatic Compression (Default Behavior)

PhpSPA automatically initializes the HTML compressor when you create your `App` instance. The second parameter controls this behavior and is set to `true` by default.

```php
<?php
use PhpSPA\App;

// Auto-initialization is enabled by default
$app = new App($layout);

// This is the same as:
$app = new App($layout, true);
```

!!! success "Smart Detection"
    When auto-initialization is enabled, PhpSPA automatically detects the best compression level based on your server environment. You don't need to manually configure anything!

### Disabling Auto-Initialization

If you want full manual control over compression, you can disable auto-initialization:

```php
<?php
use PhpSPA\App;

// Disable auto-initialization
$app = new App($layout, false);

// Now you must manually configure compression if you want it
$app->compression(Compressor::LEVEL_AGGRESSIVE, true);
```

!!! tip "When to Disable"
    Disable auto-initialization only if you need precise control over compression settings for specific use cases.

## Environment-Based Configuration (Recommended)

The easiest way to manage compression is to set the application's environment. PhpSPA will then apply a sensible preset for you.

=== "development"

    Compression is disabled to make debugging easier.

    ```php
    <?php
    use PhpSPA\Compression\Compressor;

    $app->compressionEnvironment(Compressor::ENV_DEVELOPMENT);
    ```

=== "production"

    A high level of compression is enabled for maximum performance.

    ```php
    <?php
    use PhpSPA\Compression\Compressor;

    $app->compressionEnvironment(Compressor::ENV_PRODUCTION);
    ```

!!! tip "Environment Presets"
    Set the environment to automatically configure compression with sensible defaults.

## Manual Compression Control

For more granular control, you can manually set the compression level and enable or disable Gzip.

```php
<?php
use PhpSPA\App;
use PhpSPA\Compression\Compressor;

$app = new App($layout);

// Manually set the highest level of compression and enable Gzip
$app->compression(Compressor::LEVEL_EXTREME, true);
```

!!! success "Compression Levels"
    There are several levels available, from basic to extreme:

    - **`Compressor::LEVEL_NONE`**: No compression is applied
    - **`Compressor::LEVEL_AUTO`**: Auto-detect a sensible level for the environment
    - **`Compressor::LEVEL_BASIC`**: Whitespace removal with light minification; most comments are left untouched
    - **`Compressor::LEVEL_AGGRESSIVE`**: Basic minification **plus full comment stripping**
    - **`Compressor::LEVEL_EXTREME`**: Everything the engine can do — comment stripping **and** symbol mangling for the smallest possible output

!!! info "Levels behave per-engine"
    The level is a shared intent; each JS engine honours it in its own way. The three-tier behaviour above describes the **Oxc** engine precisely (see [JavaScript Minification Engines](#javascript-minification-engines)). The C++ and esbuild engines apply their own equivalent of each tier.

## Asset Caching Behavior

!!! success "Smart Caching with Auto-Invalidation"
    When compression is enabled (any level above `LEVEL_NONE`), PhpSPA caches compressed assets to `.generated.php` files for faster subsequent requests. The cache automatically invalidates when source files change!

### How Asset Caching Works

When you enable compression, PhpSPA generates cached files alongside your component files:

```
examples/components/
├── Counter.php
└── generated/
    ├── a3f8b2c1d5e9f7a4b2c8d1e6f9a3b7c2.generated.php       # Cached compressed JS
    └── d4e7a1b8c5f2e9d6a3b7c1f4e8a2d5b9.generated.php       # Cached compressed CSS
```

!!! info "Smart Cache Invalidation"
    Cache filenames are MD5 hashes of the original filename and file modification time. When a source file changes, its modification time updates, generating a new hash and automatically creating a fresh cache file.

!!! tip "Custom Cache Location"
    You can customize where cache files are stored using `setGeneratedCacheDirectory()`. See [Asset Caching: Custom Cache Directory](assets-caching.md#custom-cache-directory) for details.

**Smart Cache Invalidation:** PhpSPA automatically detects when source files change by using file modification timestamps. When a file changes:

1. The file's modification time updates
2. A new cache hash is generated (based on filename + modification time)
3. The new cache file is created with fresh compressed content
4. Old cache files naturally expire (can be cleaned up periodically)

This means:

- ✅ **Production**: Excellent performance - compressed content is reused when unchanged
- ✅ **Development**: Always fresh - file changes automatically generate new cache files
- ✅ **No manual tracking**: File system timestamps handle everything automatically

### Recommended Environment Setup

Use environment-based configuration to automatically handle caching:

```php
<?php
use PhpSPA\App;

// Read from environment variable
$app = new App($layout);
$app->compressionEnvironment($_ENV['APP_ENV']);
```

Then configure your `.env` file:

=== "Development (.env)"

    ```bash
    APP_ENV=development
    ```
    
    - Compression disabled (`LEVEL_NONE`)
    - No cached files generated
    - Changes appear immediately (no compression overhead)
    - Existing cached files automatically deleted when switching to development

=== "Production (.env)"

    ```bash
    APP_ENV=production
    ```
    
    - Maximum compression (`LEVEL_EXTREME`)
    - Assets cached for performance
    - Blazing fast subsequent requests
    - Generated files reused until source changes detected

!!! tip "Environment Switching"
    When you switch from production back to development mode (`LEVEL_NONE`), PhpSPA automatically **deletes** cached `.generated.php` files to prevent stale content issues.

### Manual Cache Management

The cache is automatically managed, but you can manually clear it if needed:

```bash
# Delete all generated cache files
find . -name "*.generated.php" -type f -delete

# Or clean up a specific directory
rm -rf /path/to/cache/directory/*.generated.php
```

Or temporarily disable compression to bypass the cache entirely:

```php
<?php
// Force fresh assets without caching
$app->compression(Compressor::LEVEL_NONE);
```

!!! tip "Automatic Cache Invalidation"
    PhpSPA uses file modification timestamps to automatically invalidate cache. When you edit a source file, a new cache file is generated on the next request. Old cache files can be periodically cleaned up, but they won't be served since the hash no longer matches.

### IIFE Wrapping for Component Scripts

PhpSPA automatically wraps component JavaScript in Immediately Invoked Function Expressions (IIFE) for scope isolation:

- **Component JS**: Always wrapped in IIFE `(()=>{/* your code */})()`
- **Global JS**: Never wrapped to prevent duplicate execution and to allow global functions to be reused
- **External script links**: Never wrapped (assumed pre-bundled)

This ensures:
- Component scripts don't pollute the global scope
- Variables and functions are properly isolated
- Global scripts are not wrapped to allow other scripts (global and components) to reuse variables, functions and classes.

!!! tip "Global scripts still get minified inside"
    "Never wrapped" refers to the IIFE only. The global script's **body** is still minified — and with the Oxc engine at `LEVEL_EXTREME`, inner-scope functions/classes/variables are renamed to short names while the reusable **top-level** names are preserved. See [What each level does with Oxc](#oxc-minifier).

---

## ⚙️ Native Compressor Configuration { #native-compressor-configuration }

!!! success "JavaScript engines"
    :material-new-box: JavaScript is minified by the in-process **C++** engine by default. Opt into **Oxc** (`useOxcForJsMinification()`) or **esbuild** (`useEsbuildForJsMinification()`) — see [JavaScript Minification Engines](#javascript-minification-engines).

---

### ⚡ FFI Preloading { #ffi-preloading }

FFI preloading loads the native compressor library once at server startup via OPcache, removing per-request FFI initialization overhead for maximum performance.

#### Create `phpspa_compressor.h`

Create this header file anywhere on your server (e.g. your project root):

```c
// path to compressor library
#define FFI_LIB "/path/to/project/vendor/dconco/phpspa/src/bin/libcompressor.so"
#define FFI_SCOPE "phpspa_compressor"

char* phpspa_compress_html(const char* content, int level, int type, size_t* out_len);
char* phpspa_compress_html_esbuild(const char* content, int level, int type, const char* scope, char* debug_output, size_t* out_len);
void phpspa_free_string(char* ptr);
```

Then add to your `php.ini`:

```ini
ffi.enable = "preload"
ffi.preload = "/absolute/path/to/phpspa_compressor.h"
opcache.enable = 1
```

!!! info "Path Configuration"
    - **`FFI_LIB`** — Absolute path to the compressor library. Located at `vendor/dconco/phpspa/src/bin/libcompressor.so` inside your project (e.g. `/var/www/myapp/vendor/dconco/phpspa/src/bin/libcompressor.so`).
    - **`ffi.preload`** — Absolute path to the `phpspa_compressor.h` file you created (e.g. `/var/www/myapp/phpspa_compressor.h`).

!!! warning "Restart Required"
    Restart your web server after editing `php.ini` for preloading to take effect.

---

### 🚀 JavaScript Minification Engines { #javascript-minification-engines }

PhpSPA can minify your JavaScript with one of **three** engines. By default it uses the fast, in-process **C++ minifier** — no external process, no extra download, no setup. The other two are opt-in.

| Engine | How it runs | Speed | Setup | Best for |
|--------|-------------|-------|-------|----------|
| **C++** (default) | In-process, bundled | Fast | None | Everyone — zero config |
| **Oxc** | In-process via FFI (Rust) | ⚡ Fastest (milliseconds) | Download the release binary + point PhpSPA at it | Aggressive JS minification without a process-per-asset penalty |
| **esbuild** | External Node process | Slow (seconds; slower as more processes spawn) | `npm i -g esbuild` | Bundling / tree-shaking when you specifically need esbuild |

!!! warning "Breaking change"
    In earlier versions esbuild was the **default**. It no longer is — **C++ is the default**. If you relied on esbuild-by-default you must now opt in explicitly with `useEsbuildForJsMinification()`. The old `disableMinificationWithEsbuild()` method is **deprecated** and is now a **no-op** (esbuild is no longer the default, so there is nothing to disable) — existing calls keep working but have no effect, and the method **will be removed in a future version**. Opt into esbuild with `useEsbuildForJsMinification()` instead.

#### Opting into esbuild

```php
<?php
use PhpSPA\App;

$app = new App($layout);
$app->useEsbuildForJsMinification();
```

!!! info "Installing esbuild"
    ```bash
    npm install --global esbuild
    ```
    If esbuild is not found globally, PhpSPA falls back to `npx`, which downloads it on first run (slower).

!!! warning "esbuild is slow"
    esbuild runs an **external process per asset**, so compression time is measured in **seconds**, not milliseconds, and degrades as more processes spawn. Prefer the C++ default or Oxc unless you specifically need esbuild's bundling/tree-shaking.

---

### ⚡ Oxc JavaScript Minifier { #oxc-minifier }

[Oxc](https://oxc.rs/) is a Rust-based JavaScript toolchain. PhpSPA can load its minifier through FFI for **extremely fast** (millisecond) in-process minification — far faster than esbuild, with no process-per-asset penalty.

!!! info "The Oxc library is not shipped with PhpSPA"
    Unlike the C++ compressor (whose binary ships inside the package), the Oxc shared library is **not** bundled. You download the platform binary from the [GitHub Releases](https://github.com/dconco/phpspa/releases/latest) and point PhpSPA at it yourself.

| Platform | Library File |
|----------|-------------|
| :fontawesome-brands-windows: Windows | `oxc_php_minifier.dll` |
| :fontawesome-brands-linux: Linux | `liboxc_php_minifier.so` |
| :fontawesome-brands-apple: macOS | `liboxc_php_minifier.dylib` |

#### Enabling Oxc

```php
<?php
use PhpSPA\App;

$app = new App($layout);

$app->useOxcForJsMinification()
    ->setOxcLibraryPath('/absolute/path/to/oxc_php_minifier.dll');
```

Oxc loads in one of two ways:

=== "Dynamic load (simplest)"

    Point PhpSPA at the downloaded binary — no `php.ini` edit needed:

    ```php
    <?php
    $app->useOxcForJsMinification()
        ->setOxcLibraryPath(__DIR__ . '/bin/oxc_php_minifier.dll');
    ```

    Or via environment variable:

    ```bash
    export PHPSPA_OXC_LIB="/absolute/path/to/liboxc_php_minifier.so"
    ```

=== "Preload (fastest in production)"

    Preload the library once at server startup via OPcache. Create `phpspa_oxc.h`:

    ```c
    #define FFI_LIB "/absolute/path/to/liboxc_php_minifier.so"
    #define FFI_SCOPE "phpspa_oxc"

    char* oxc_minify(const char* source, const char* scope, int level);
    void oxc_free_string(char* ptr);
    ```

    Then in `php.ini`:

    ```ini
    ffi.enable = "preload"
    ffi.preload = "/absolute/path/to/phpspa_oxc.h"
    opcache.enable = 1
    ```

    Restart your web server afterwards. When preloaded, `setOxcLibraryPath()` is not required.

#### What each level does with Oxc

Oxc distinguishes **global** scripts (reused across the page, e.g. a UMD-wrapped loader) from **component (scoped)** scripts (wrapped in an IIFE):

| Level | Global script | Component (scoped) script |
|-------|---------------|---------------------------|
| **`LEVEL_BASIC`** | Minified; top-level **and** inner names preserved; comments left as-is | Minified + IIFE-wrapped; comments left as-is |
| **`LEVEL_AGGRESSIVE`** | Same as basic **+ all comments stripped** | Same as basic **+ all comments stripped** |
| **`LEVEL_EXTREME`** | Comments stripped; **top-level names preserved** (so other scripts can still reference them) while **inner-scope functions/classes/variables are mangled** to short names | Comments stripped; **everything mangled** + IIFE-wrapped |

!!! tip "Why top-level names survive in global scripts"
    Global scripts expose functions, classes and variables that your page and your component scripts reference by name. Oxc keeps those top-level names intact at every level, and reserves the UMD `exports`/`module` bindings, while still shrinking the code inside each function body at `LEVEL_EXTREME`.

#### Oxc fallback behaviour

!!! info "Oxc degrades to PHP, never to C++"
    If Oxc is enabled but unavailable (not preloaded, no path set, or FFI disabled), PhpSPA falls back to the **PHP** minifier — not the C++ engine. The only exception is [`forceNativeCompression()`](#force-native-compression): with that enabled, an unavailable Oxc throws a `RuntimeException` instead of degrading.

!!! note "Invalid JavaScript"
    If a developer's JS fails to parse, Oxc does not throw and does not silently drop it — it returns a `console.error(...)` payload so the broken script is visible in the browser console and the page still loads.

---

### 📂 Custom Compressor Library Path

You can specify an absolute path to the native compressor shared library. This is useful if the library is in a non-standard location or you want to use a specific version.

=== "Using the method (recommended)"

    ```php
    <?php
    use PhpSPA\App;

    $app = new App($layout);
    $app->setCustomCompressorLibraryPath('/path/to/libcompressor.so');
    ```

=== "Using environment variable"

    ```bash
    export PHPSPA_COMPRESSOR_LIB="/path/to/libcompressor.so"
    ```

    ```php
    <?php
    // Or set it in PHP before creating the App
    putenv("PHPSPA_COMPRESSOR_LIB=/path/to/libcompressor.so");

    $app = new \PhpSPA\App($layout);
    ```

!!! tip "Platform-specific library names"
    | Platform | Library File |
    |----------|-------------|
    | :fontawesome-brands-windows: Windows | `compressor.dll` |
    | :fontawesome-brands-linux: Linux | `libcompressor.so` |
    | :fontawesome-brands-linux: WSL | `libcompressor-wsl.so` |
    | :fontawesome-brands-apple: macOS | `libcompressor.dylib` |

---

### 🔒 Force Native Compression { #force-native-compression }

By default, PhpSPA falls back to the **PHP** minifier when a native engine is unavailable. `forceNativeCompression()` turns that fallback off: if the selected native engine (C++, esbuild, or Oxc) cannot run, the app throws a `RuntimeException` instead of degrading to PHP.

```php
<?php
use PhpSPA\App;

$app = new App($layout);
$app->forceNativeCompression();
```

!!! info "It controls the fallback policy, not the engine"
    `forceNativeCompression()` does **not** pick an engine — it only says *"never fall back to PHP"*. It applies to whichever JS engine you selected:

    - **C++ / esbuild** — a failure throws instead of dropping to PHP (esbuild still falls back to C++ first, since they share the same binary).
    - **Oxc** — an unavailable Oxc throws instead of degrading to PHP. (Oxc never falls back to C++ in either mode.)

!!! warning "Use with caution"
    Only enable this where you are certain the native engine is available. If it fails to load, your application throws instead of serving a PHP-minified page.

=== "Recommended: Production"

    ```php
    <?php
    $app->forceNativeCompression();
    $app->setCustomCompressorLibraryPath('/opt/phpspa/libcompressor.so');
    ```

=== "Recommended: Development"

    ```php
    <?php
    // Let PhpSPA auto-detect and fallback gracefully
    // No need to call forceNativeCompression()
    ```

---

### 🧩 Complete Configuration Example

```php
<?php
use PhpSPA\App;
use PhpSPA\Compression\Compressor;

$app = new App($layout);

// Never fall back to the PHP minifier — throw if a native engine fails
$app->forceNativeCompression();

// Point to a specific C++ compressor build (optional)
$app->setCustomCompressorLibraryPath(__DIR__ . '/bin/libcompressor.so');

// Use extreme compression with Gzip
$app->compression(Compressor::LEVEL_EXTREME, true);

// JavaScript engine (choose ONE — C++ is the default if you call neither):
//
//   Oxc — fastest, in-process via FFI (download the release binary):
// $app->useOxcForJsMinification()
//     ->setOxcLibraryPath(__DIR__ . '/bin/oxc_php_minifier.so');
//
//   esbuild — external Node process, slower, adds bundling/tree-shaking:
// $app->useEsbuildForJsMinification();

$app->run();
```

!!! info "Method Chaining"
    All configuration methods return `$this`, so you can chain them:

    ```php
    <?php
    $app->forceNativeCompression()
        ->useOxcForJsMinification()
        ->setOxcLibraryPath(__DIR__ . '/bin/oxc_php_minifier.so')
        ->compression(Compressor::LEVEL_EXTREME, true)
        ->run();
    ```
