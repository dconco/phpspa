<?php

namespace phpSPA;

use phpSPA\Http\Session;
use phpSPA\Core\Config\CompressionConfig;
use phpSPA\Core\Helper\AssetLinkManager;

/**
 *
 * Class App
 *
 * The main application class for phpSPA.
 * Handles layout composition, component mounting, and rendering flow.
 *
 * Features in v1.1.5:
 * - Method chaining for fluent configuration
 * - HTML compression and minification
 * - Environment-based compression settings
 *
 * @package phpSPA
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/1-introduction
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/4-method-chaining/ Method Chaining Documentation
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 * @link https://phpspa.readthedocs.io/en/latest/1-introduction
 * @extends \phpSPA\Core\Impl\RealImpl\AppImpl
 * @implements \phpSPA\Interfaces\phpSpaInterface
 */
class App extends \phpSPA\Core\Impl\RealImpl\AppImpl implements
    \phpSPA\Interfaces\phpSpaInterface
{
    /**
     * App constructor.
     *
     * Initializes the App instance with the specified layout.
     *
     * @param callable $layout The name of the layout to be used by the application.
     * @param bool $autoInitCompression Whether to auto-initialize compression settings
     */
    public function __construct(callable $layout, bool $autoInitCompression = true)
    {
        Session::start();
        $this->layout = $layout;
        self::$request_uri = urldecode(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        );

        // Initialize HTML compression based on environment
        if ($autoInitCompression) {
            CompressionConfig::autoDetect();
        }
    }

    /**
     * Configure HTML compression manually
     *
     * @param int $level Compression level (0=none, 1=basic, 2=aggressive, 3=extreme)
     * @param bool $gzip Enable gzip compression
     * @return self
     */
    public function compression(int $level, bool $gzip = true): self
    {
        CompressionConfig::custom($level, $gzip);
        return $this;
    }

    /**
     * Set compression based on environment
     *
     * @param string $environment Environment: 'development', 'staging', 'production'
     * @return self
     */
    public function compressionEnvironment(string $environment): self
    {
        CompressionConfig::initialize($environment);
        return $this;
    }

    /**
     * Set cache duration for CSS/JS assets
     *
     * @param int $hours Number of hours to cache assets (0 for session-only)
     * @return self
     */
    public function assetCacheHours(int $hours): self
    {
        AssetLinkManager::setCacheConfig($hours);
        return $this;
    }
}
