<?php

namespace PhpSPA;

use PhpSPA\Http\Session;
use PhpSPA\Core\Config\CompressionConfig;

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
 * @see https://phpspa.vercel.app/core-concepts
 * @link https://phpspa.vercel.app
 */
class App extends \PhpSPA\Core\Impl\RealImpl\AppImpl {
    /**
     * App constructor.
     *
     * Initializes the App instance with the specified layout.
     *
     * @param callable $layout The name of the layout to be used by the application.
     * @param bool $autoInitCompression Whether to auto-initialize compression settings
     * @see https://phpspa.vercel.app/layout
     * @see https://phpspa.vercel.app/performance/html-compression
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
}
