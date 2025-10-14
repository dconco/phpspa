<?php

namespace PhpSPA;

use PhpSPA\Http\Session;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Core\Helper\AssetLinkManager;

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
 * @package PhpSPA
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 *
 * @see https://phpspa.readthedocs.io/en/latest/core-concepts-app-and-component/
 * @link https://phpspa.readthedocs.io
 */
class App extends \PhpSPA\Core\Impl\RealImpl\AppImpl implements
    \PhpSPA\Interfaces\phpSpaInterface {

    /**
     * App constructor.
     *
     * Initializes the App instance with the specified layout.
     *
     * @param callable $layout The name of the layout to be used by the application.
     * @param bool $autoInitCompression Whether to auto-initialize compression settings
     */
    public function __construct (callable|string $layout, bool $autoInitCompression = true)
    {
        Session::start();
        $this->layout = $layout;
        self::$request_uri = urldecode(
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        );

        // Initialize HTML compression based on environment
        if ($autoInitCompression) {
            CompressionConfig::autoDetect();
        }
    }


    public function compression (int $level, bool $gzip = true): self
    {
        CompressionConfig::custom($level, $gzip);
        return $this;
    }


    public function compressionEnvironment (string $environment): self
    {
        CompressionConfig::initialize($environment);
        return $this;
    }


    public function assetCacheHours (int $hours): self
    {
        AssetLinkManager::setCacheConfig($hours);
        return $this;
    }


    public function script (callable $script, ?string $name = null): self
    {
        $this->scripts[] = [ $script, $name ];
        return $this;
    }


    public function styleSheet (callable $style, ?string $name = null): self
    {
        $this->stylesheets[] = [ $style, $name ];
        return $this;
    }
}
