<?php

namespace phpSPA;

/**
 * Class App
 *
 * The main application class for phpSPA.
 * Handles layout composition, component mounting, and rendering flow.
 *
 * @package phpSPA\App
 * @extends \phpSPA\Impl\RealImpl\AppImpl
 * @implements \phpSPA\Interfaces\phpSpaInterface
 */
class App extends \phpSPA\Impl\RealImpl\AppImpl implements \phpSPA\Interfaces\phpSpaInterface
{
   /**
    * App constructor.
    *
    * Initializes the App instance with the specified layout.
    *
    * @param callable $layout The name of the layout to be used by the application.
    */
   public function __construct (callable $layout)
   {
      $this->layout = $layout;
      self::$request_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
   }
}