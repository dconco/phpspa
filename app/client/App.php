<?php

namespace PhpSPA;

use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Core\Impl\RealImpl\AppImpl;
use PhpSPA\Interfaces\ApplicationContract;

/**
 *
 * Class App
 *
 * The main application class for PhpSPA.
 * Handles layout composition, component mounting, and rendering flow.
 *
 * Features in v1.1.5:
 * - Method chaining for fluent configuration
 * - HTML compression and minification
 * - Environment-based compression settings
 *
 * @method ApplicationContract attach(callable|Component $component) Attach a component to the application
 * @method ApplicationContract detach(\PhpSPA\Interfaces\IComponent|Component $component) Detach a component from the application
 * @method ApplicationContract defaultTargetID(string $targetID) Sets the target ID for the application
 * @method ApplicationContract defaultToCaseSensitive() Sets the default behavior to case sensitive
 * @method ApplicationContract compression(int $level, bool $gzip = true) Configure HTML compression manually
 * @method ApplicationContract assetCacheHours(int $hours) Set cache duration for CSS/JS assets
 * @method ApplicationContract compressionEnvironment(string $environment) Set compression based on environment
 * @method ApplicationContract script(callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []) Add a global script to the application
 * @method ApplicationContract styleSheet(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []) Add a global stylesheet to the application
 * @method ApplicationContract link(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []) Add a global link tag to the application
 * @method ApplicationContract meta(?string $name = null, ?string $content = null, ?string $property = null, ?string $httpEquiv = null, ?string $charset = null, array $attributes = []) Register global meta tags
 * @method ApplicationContract useStatic(string $route, string $staticPath) Registers a static file path to a route
 * @method ApplicationContract middleware(callable|string $component) Register a global middleware that applies to all components
 * @method ApplicationContract prefix(string $path, callable|string $handler) Prefix routes
 * @method ApplicationContract useModule() Enable this to prevent the application from adding the phpspa CDN script tag, allowing you to load it yourself with the phpspa js library `npm i @dconco/phpspa` and use it in your own custom way.
 * @method ApplicationContract useEsbuildForJsMinification() Opt into esbuild for JS minification (more aggressive, AST-accurate). Default is the fast in-process C++ JS minifier.
 * @method ApplicationContract disableMinificationWithEsbuild() @deprecated No-op since v2.1.0 (esbuild is no longer the default, so nothing to disable); will be removed in a future version. Use useEsbuildForJsMinification() to opt into esbuild.
 * @method ApplicationContract useOxcForJsMinification() Opt into the Oxc (Rust) in-process JS minifier — recommended high-performance engine. Preload-only (see examples/phpspa_oxc.h); takes priority over esbuild/native. No fallback.
 * @method ApplicationContract randomizeAssetName() Randomize asset names
 * @method ApplicationContract disableLogs(int $flags) Disable logging for the specified log types.
 * @method ApplicationContract combineAssetsPerComponent() Combine all same-type assets per component (and per global scope) into a single link each. Prototype, recommended for production only. Force-enables randomizeAssetName().
 * @method ApplicationContract setGeneratedCacheDirectory(string $path) Set the generated cache directory
 * @method ApplicationContract setCustomCompressorLibraryPath(string $path) Set a custom compressor library path
 * @method ApplicationContract setOxcLibraryPath(string $path) Set the Oxc JS minifier library path (PHPSPA_OXC_LIB), used for a direct FFI::cdef load when Oxc isn't preloaded
 * @method ApplicationContract forceNativeCompression() Force native compression
 * @method ApplicationContract cors(array $data = []) Configure CORS
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 *
 * @see https://phpspa.tech/core-concepts
 * @link https://phpspa.tech
 */
class App extends AppImpl implements ApplicationContract {
    /**
     * App constructor.
     *
     * Initializes the App instance with the specified layout.
     *
     * @param callable $layout The name of the layout to be used by the application.
     * @param bool $autoInitCompression Whether to auto-initialize compression settings
     * @see https://phpspa.tech/layout
     * @see https://phpspa.tech/performance/html-compression
     */
    public function __construct (callable|string $layout = "", bool $autoInitCompression = true)
    {
        $this->layout = $layout;
        static::$request_uri = (new HttpRequest())->path();

        // Assign a distinct, construction-order global-asset scope id so each
        // App instance's global scripts/stylesheets bind to it alone.
        $this->initGlobalScope();

        // Initialize HTML compression based on environment
        if ($autoInitCompression) {
            CompressionConfig::autoDetect();
        }
    }

}
