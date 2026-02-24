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
 * 
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

    public function defaultTargetID(string $targetID): self
    {
        // Implementation for setting the default target ID
        return $this;
    }

    public function defaultToCaseSensitive(): self
    {
        // Implementation for setting case sensitivity
        return $this;
    }

    public function compression(int $level, bool $gzip = true): self
    {
        // Implementation for configuring HTML compression
        return $this;
    }

    public function assetCacheHours(int $hours): self
    {
        // Implementation for setting asset cache duration
        return $this;
    }

    public function compressionEnvironment(string $environment): self
    {
        // Implementation for environment-based compression
        return $this;
    }

    public function script(callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []): self
    {
        // Implementation for adding a global script
        return $this;
    }

    public function styleSheet(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self
    {
        // Implementation for adding a global stylesheet
        return $this;
    }

    public function link(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self
    {
        // Implementation for adding a global link tag
        return $this;
    }

    public function meta(?string $name = null, ?string $content = null, ?string $property = null, ?string $httpEquiv = null, ?string $charset = null, array $attributes = []): self
    {
        // Implementation for registering global meta tags
        return $this;
    }

    public function useStatic(string $route, string $staticPath): self
    {
        // Implementation for registering a static file path to a route
        return $this;
    }

    public function middleware(callable|string $component): self
    {
        // Implementation for registering global middleware
        return $this;
    }

    public function prefix(string $path, callable|string $handler): self
    {
        // Implementation for prefixing routes
        return $this;
    }

    public function useModule(): self
    {
        // Implementation for using a module
        return $this;
    }

    public function useEsbuild(): self
    {
        // Implementation for enabling esbuild
        return $this;
    }

    public function randomizeAssetName(): self
    {
        // Implementation for randomizing asset names
        return $this;
    }

    public function setGeneratedCacheDirectory(string $path): self
    {
        // Implementation for setting the generated cache directory
        return $this;
    }

    public function setCustomCompressorLibraryPath(string $path): self
    {
        // Implementation for setting a custom compressor library path
        return $this;
    }

    public function forceNativeCompression(): self
    {
        // Implementation for forcing native compression
        return $this;
    }

    public function cors(array $data = []): self
    {
        // Implementation for configuring CORS
        return $this;
    }

    public function attach(IComponent|Component $component): self
    {
        // Implementation for attaching a component
        return $this;
    }

    public function detach(IComponent|Component $component): self
    {
        // Implementation for detaching a component
        return $this;
    }

    public function run(bool $return = false)
    {
        // Implementation for running the application
    }
}
