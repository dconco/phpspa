<?php

require_once '../vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

var_dump(Compressor::supportsGzip());

/* Initialize a new Application */
$app = (new App(require 'Layout.php'))

	/* Attach and Run Application */
	->attach(require 'components/Login.php')
	->attach(require 'components/Timer.php')
	->attach(require 'components/Counter.php')
	->attach(require 'components/HomePage.php')

	->defaultTargetID('app')
	->defaultToCaseSensitive()

	/*->compression(Compressor::LEVEL_EXTREME, false)*/
	->compressionEnvironment(Compressor::ENV_PRODUCTION)

	->cors();

$app->run();
