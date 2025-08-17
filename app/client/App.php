<?php

namespace phpSPA;

use phpSPA\Http\Session;

/**
 *
 * Class App
 *
 * The main application class for phpSPA.
 * Handles layout composition, component mounting, and rendering flow.
 *
 * @package phpSPA
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/1-introduction
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
	 */
	public function __construct(callable $layout)
	{
		Session::start();
		$this->layout = $layout;
		self::$request_uri = urldecode(
			parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
		);
	}
}
