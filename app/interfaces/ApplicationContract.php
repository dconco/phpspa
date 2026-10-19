<?php

namespace PhpSPA\Interfaces;

use PhpSPA\Component;

/**
 * Core PhpSPA application contract
 * 
 * This application provides the foundational implementation for the PhpSPA application framework.
 * It handles layout management, component registration,
 * routing, and rendering logic that powers the single-page application experience.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @see https://phpspa.tech/core-concepts
 */
interface ApplicationContract {
    /**
     * Sets the target ID for the application.
     *
     * @param string $targetID The target ID to be set.
     * @return self
     * @see https://phpspa.tech/layout/#setting-the-default-target-id
     */
    public function defaultTargetID (string $targetID): self;


    /**
     * Sets the default behavior to case sensitive.
     *
     * Implementing this method should configure the system or component
     * to treat relevant operations (such as string comparisons or lookups)
     * as case sensitive by default.
     *
     * @return self
     * @see https://phpspa.tech/routing/component-configuration/#global-case-sensitivity
     */
    public function defaultToCaseSensitive (): self;


    /**
     * Configure HTML compression manually
     *
     * @param int $level Compression level (0=none, 1=auto, 2=basic, 3=aggressive, 4=extreme)
     * @param bool $gzip Enable gzip compression
     * @return self
     * @see https://phpspa.tech/performance/html-compression
     */
    public function compression (int $level, bool $gzip = true): self;


    /**
     * Set cache duration for CSS/JS assets
     *
     * @param int $hours Number of hours to cache assets (0 for session-only) Default is 24 hours
     * @return self
     * @see https://phpspa.tech/performance/assets-caching
     */
    public function assetCacheHours (int $hours): self;


    /**
     * Set compression based on environment
     *
     * @param string $environment Environment: 'development', 'staging', 'production'
     * @return self
     * @see https://phpspa.tech/performance/html-compression/#environment-based-configuration-recommended
     */
    public function compressionEnvironment (string $environment): self;


    /**
     * Add a global script to the application
     *
     * This script will be executed on every component render throughout the application.
     * Scripts are added to the global scripts array and will be rendered alongside
     * component-specific scripts.
     *
     * @param callable|string $content The callable that returns the JavaScript code
     * @param string|null $name Optional name for the script asset
     * @param string|null $type The type of script the content should be treated as
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     */
    public function script (callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []): self;


    /**
     * Add a global stylesheet to the application
     *
     * This stylesheet will be included on every component render throughout the application.
     * Stylesheets are added to the global stylesheets array and will be rendered alongside
     * component-specific styles.
     *
     * @deprecated Use `App::link()` instead
     * @param callable|string $content The callable that returns the CSS code
     * @param string|null $name Optional name for the stylesheet asset
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     */
    public function styleSheet (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;


    /**
     * Add a global link tag to the application
     *
     * This stylesheet will be included on every component render throughout the application.
     * Stylesheets are added to the global stylesheets array and will be rendered alongside
     * component-specific styles.
     *
     * @param callable|string $content The callable that returns the CSS code
     * @param string|null $name Optional name for the stylesheet asset
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     * @since v2.0.5
     */
    public function link (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;

    /**
     * Register global meta tags that render with every initial HTML response.
     *
     * Entries follow the same API as component-level metadata and merge with
     * per-component tags (component tags win when duplicates exist).
     *
     * @param string|null $name Standard meta "name" attribute.
     * @param string|null $content Content associated with the meta tag.
     * @param string|null $property Open Graph "property" attribute value.
     * @param string|null $httpEquiv HTTP-EQUIV attribute value.
     * @param string|null $charset Charset declaration (mutually exclusive with content).
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @since v2.0.5
     * @see https://phpspa.tech/references/component-meta
     */
    public function meta(
        ?string $name = null,
        ?string $content = null,
        ?string $property = null,
        ?string $httpEquiv = null,
        ?string $charset = null,
        array $attributes = []
    ): self;


    /**
     * Registers a static file path to a route.
     *
     * @param string $route The route to map.
     * @param string $staticPath The static file path.
     * @return ApplicationContract
     * @see https://phpspa.tech/references/router/#static-files
     * @since v2.0.5
     */
    public function useStatic(string $route, string $staticPath): self;

    /**
     * Register a global middleware that applies to all components.
     *
     * Middleware functions receive two parameters:
     *
     * - $req: The current Request instance.
     * - $next: A callable that represents the next middleware or the component itself.
     *          When called, it returns the HTML string output of the component/next middleware.
     *
     * Signature: `callable(Request $req, Closure $next): mixed`
     *
     * @example
     * ```php
     * new App()->middleware(function (Request $req, Closure $next) {
     *    if ($req->auth()->bearer) {
     *        return $next();
     *    }
     * 
     *    http_response_code(403);
     *    return "<h2>Unauthorized</h2>";
     * });
     * ```
     * 
     * @param callable|string $middleware The middleware function.
     * @return self
     * @since v2.0.5
     * @see https://phpspa.tech/references/middleware/#app-middleware
     */
    public function middleware(callable|string $middleware): self;

    /**
     * Group routes under a common prefix.
     *
     * @param string $path The prefix path.
     * @param callable|string $handler The handler function with Router as the parameter.
     * @return self
     * @since v2.0.4
     * @see https://phpspa.tech/references/router/#app-level-prefixing
    */
    public function prefix(string $path, callable|string $handler): self;

    /**
     * If you are using script module with `@dconco/phpspa` js package, enable this function
     * 
     * @since v2.0.5
     */
    public function useModule(): self;

    /**
     * Overrides custom asset names and randomizes them, typically used for cache busting in production.
     *
     * This method modifies the internal representation of an asset's name
     * by replacing any custom names with a random or unique hash string.
     * This is primarily intended for production environments to ensure browsers
     * always fetch the latest version of assets (like CSS or JavaScript files)
     * instead of serving cached versions when the file content has changed.
     *
     * It is recommended to remove this method call in development environments for debugging purposes.
     *
     * @since v2.0.7
     */
    public function randomizeAssetName(): self;

    /**
     * Disables logging for the specified log types.
     *
     * The native compressor emits several informational `error_log` messages during
     * startup and compression. Pass a bitmask of the following flags to silence them.
     * Combine flags with the bitwise OR operator (`|`); omit the argument to disable all.
     *
     * - `DISABLE_PRELOAD_LOG` — silences the "phpspa_compressor isn't preloaded in PHP INI,
     *   falling back to loading directly from the shared library." notice, logged when the
     *   compressor was not preloaded via FFI and is loaded from the shared library instead.
     * - `DISABLE_LIBRARY_LOG` — silences the "Found native compressor library: ..." / "Using
     *   native compressor library from environment variable: ..." notices logged while locating
     *   and loading the native compressor library.
     * - `DISABLE_COMPRESSION_LOG` — silences the "Compressed ... output within ... | From ..."
     *   notice logged after each successful HTML/asset compression.
     *
     * @param int $flags Bitmask of log types to disable (`DISABLE_PRELOAD_LOG`,
     *                   `DISABLE_LIBRARY_LOG`, `DISABLE_COMPRESSION_LOG`). Defaults to all three.
     * @return self
     * @since v2.0.14
     * @see https://phpspa.tech/references/disabling-logs/
     */
    public function disableLogs(int $flags = DISABLE_COMPRESSION_LOG | DISABLE_LIBRARY_LOG | DISABLE_PRELOAD_LOG): self;

    /**
     * Sets the directory path for generated assets cache files.
     *
     * This method allows you to specify a custom directory where
     * the application will store generated cache files, such as compiled assets
     * or pre-rendered components. By setting this path, you can control
     * where cache files are saved, which can be useful for organizing
     * your application's file structure or optimizing performance.
     *
     * @param string $path The directory path for generated cache files.
     * @since v2.0.8
     */
    public function setGeneratedCacheDirectory(string $path): self;

    /**
     * Enables esbuild for JavaScript minification. By default the application uses the fast in-process C++ JS minifier, which spawns no external process. Call this to opt into esbuild instead for more aggressive, AST-accurate minification of JavaScript assets.
     * esbuild runs as an external process on asset (re)generation (cache-miss), not on every request, and falls back to the C++ minifier if the esbuild process fails or times out.
     * Enable this if you want the most aggressive minification and have esbuild available in your environment; otherwise the default C++ minifier is recommended for its zero-process speed.
     *
     * @since v2.1.0
     * @see https://phpspa.tech/performance/html-compression/#native-compressor-configuration
     */
    public function useEsbuildForJsMinification(): self;

    /**
     * Disables esbuild for JavaScript minification.
     *
     * @deprecated Since v2.1.0. This method is a no-op and does nothing. In earlier
     *             versions esbuild was the default JS engine and this turned it off;
     *             now the fast in-process C++ minifier is the default and esbuild is
     *             opt-in, so there is nothing to disable. It is kept only so existing
     *             code keeps working, and it WILL BE REMOVED in a future version.
     *             To use esbuild, opt in with {@see useEsbuildForJsMinification()}.
     *
     * @see useEsbuildForJsMinification()
     * @since v2.1.0
     * @return self
     */
    public function disableMinificationWithEsbuild(): self;

    /**
     * Enables the Oxc (Rust) in-process JavaScript minifier. Oxc is the recommended
     * high-performance engine: AST-accurate (maximum minification, won't silently
     * corrupt JS) and in-process (no subprocess, unlike esbuild), making it ideal
     * for high-concurrency production traffic.
     *
     * Oxc is OPT-IN. It loads either from `ffi.preload` (preferred, FFI_SCOPE=
     * "phpspa_oxc" — see examples/phpspa_oxc.h) or, when not preloaded, dynamically
     * via `FFI::cdef` from the path set with `setOxcLibraryPath()`.
     * If Oxc is enabled but unavailable (not preloaded, no library path, FFI
     * disabled), it degrades to the PHP minifier — it NEVER falls back to the C++
     * minifier (that is esbuild's behaviour). Call `forceNativeCompression()` to
     * make an unavailable Oxc throw instead of degrading to PHP. A developer's own
     * invalid JS is returned as a visible console error, not thrown.
     *
     * Priority: Oxc takes precedence over every other engine when enabled. If both
     * Oxc and esbuild are enabled, Oxc wins when it is actually preloaded/available,
     * otherwise esbuild handles the request. `forceNativeCompression()` does not
     * disable Oxc — it makes an unavailable Oxc throw instead of degrading to PHP.
     *
     * Applies only to JavaScript — HTML and CSS always use the native C++ compressor.
     *
     * @since v2.1.0
     * @see https://phpspa.tech/performance/html-compression/#native-compressor-configuration
     */
    public function useOxcForJsMinification(): self;

    /**
     * Sets a custom path for the native compressor shared library.
     *
     * This method allows you to specify an absolute path to the native compressor
     * shared library (e.g., compressor.dll, libcompressor.so, libcompressor.dylib).
     * This is useful if the library is located in a non-standard directory or if you
     * want to use a specific version of the compressor.
     *
     * @param string $path Absolute path to the native compressor shared library.
     * @return self
     * @since v2.0.9
     * @see https://phpspa.tech/performance/html-compression/#native-compressor-configuration
     */
    public function setCustomCompressorLibraryPath(string $path): self;

    /**
     * Sets the path for the Oxc JS minifier shared library.
     *
     * Sets the `PHPSPA_OXC_LIB` environment variable to an absolute path of the Oxc
     * shared library (liboxc_php_minifier.so / oxc_php_minifier.dll /
     * liboxc_php_minifier.dylib). The library is then loaded dynamically with
     * `FFI::cdef` from here, instead of the preloaded `phpspa_oxc` FFI scope.
     *
     * When Oxc is enabled via {@see useOxcForJsMinification()}, you MUST provide the
     * library one of two ways: preload it in `ffi.preload` (FFI_SCOPE "phpspa_oxc"),
     * or set this path. Preloading is the faster option; use this when you prefer a
     * direct dynamic load over editing `ffi.preload`. With neither, Oxc is
     * unavailable and degrades to the PHP minifier (or throws under
     * {@see forceNativeCompression()}).
     *
     * @param string $path Absolute path to the Oxc shared library.
     * @return self
     * @since v2.1.0
     * @see https://phpspa.tech/performance/html-compression/#native-compressor-configuration
     */
    public function setOxcLibraryPath(string $path): self;

    /**
     * Forces the application to use native (C++) compression instead of the default PHP minification.
     *
     * @return self
     * @since v2.0.9
     * @see https://phpspa.tech/performance/html-compression/#native-compressor-configuration
     */
    public function forceNativeCompression(): self;

    /**
     * Configure CORS (Cross-Origin Resource Sharing) settings for the application.
     *
     * Loads default CORS configuration from the config file and optionally merges
     * custom settings provided via the data parameter. Automatically removes
     * duplicate values from array-type configuration options.
     *
     * @param array{
     *   allow_origin: array<string>,
     *   allow_methods: array<string>,
     *   allow_headers: array<string>,
     *   allow_credentials: bool,
     *   supports_credentials: bool,
     *   expose_headers: array<string>,
     *   max_age: int,
     * } $data
     *
     * @return self Returns the current instance for method chaining
     * @see https://phpspa.tech/security/cors
     */
    public function cors (array $data = []): self;


    /**
     * Attaches a component to the current object.
     *
     * @param IComponent|Component $component The component instance to attach.
     * @return self
     */
    public function attach (IComponent|Component $component): self;


    /**
     * Detaches the specified component from the current context.
     *
     * @param IComponent|Component $component The component instance to be detached.
     * @return self
     */
    public function detach (IComponent|Component $component): self;


    /**
     * Runs the application.
     *
     * This method is responsible for executing the main logic of the application,
     * including routing, rendering components, and managing the application lifecycle.
     *
     * @param bool $return If true, returns the rendered output as a string instead of printing it.
     */
    public function run (bool $return = false);

    /**
     * Combine all assets per component into single bundled links.
     *
     * When enabled, instead of generating one link per CSS/JS callable, the application
     * generates a single combined link per (scope, asset-type, script-type) group:
     * - Global CSS callables → one combined CSS link
     * - Global JS callables → one combined JS link (per script type)
     * - Component CSS callables → one combined CSS link
     * - Component JS callables → one combined JS link (per script type)
     *
     * This dramatically reduces the number of HTTP requests and compressor/esbuild
     * invocations (N callables → 1 compression pass instead of N), improving
     * page load performance in production.
     *
     * **Recommended for production only.** In development, individual links make
     * debugging easier. Enabling this also forces `randomizeAssetName()` (combined
     * bundles have no single name).
     *
     * External asset links (non-callable strings) are NOT combined and remain
     * as individual tags.
     *
     * @return self
     * @since v2.1.0
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#combining-assets-per-component
     */
    public function combineAssetsPerComponent(): self;
}
