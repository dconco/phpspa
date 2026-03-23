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
 * @method ApplicationContract detach(IComponent|Component $component) Detach a component from the application
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
 * @method ApplicationContract disableMinificationWithEsbuild() Disable esbuild for JS minification, use default C++ minification instead
 * @method ApplicationContract randomizeAssetName() Randomize asset names
 * @method ApplicationContract setGeneratedCacheDirectory(string $path) Set the generated cache directory
 * @method ApplicationContract setCustomCompressorLibraryPath(string $path) Set a custom compressor library path
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

        // Initialize HTML compression based on environment
        if ($autoInitCompression) {
            CompressionConfig::autoDetect();
        }
    }

}
