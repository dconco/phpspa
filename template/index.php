<?php

require_once '../vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

/* Initialize a new Application  */
$app = (new App(require 'Layout.php'))
    /* Attach and Run Application */

    ->attach(require 'components/Login.php')
    ->attach(require 'components/Timer.php')
    ->attach(require 'components/Counter.php')
    ->attach(require 'components/HomePage.php')

    ->defaultTargetID('app')
    ->defaultToCaseSensitive()

    ->compression(Compressor::LEVEL_NONE, true)

    ->cors();

$app->run();
