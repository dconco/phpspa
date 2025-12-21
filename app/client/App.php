<?php

namespace PhpSPA;

use PhpSPA\Http\Session;
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
 * @author dconco <me@dconco.tech>
 * @copyright 2025 Dave Conco
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
        Session::start();
        $this->layout = $layout;
        static::$request_uri = urldecode(
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        );

        // Initialize HTML compression based on environment
        if ($autoInitCompression) {
            CompressionConfig::autoDetect();
        }
    }
}
