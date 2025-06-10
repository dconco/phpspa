<?php

require '../vendor/autoload.php';

use phpSPA\App;

/* Initialize a new Application */
$app = new App(require 'Layout.php');

/* Attach and Run Application */
$app->attach(require 'components/HomePage.php');
$app->attach(require 'components/Login.php');

$app->defaultToCaseSensitive();
$app->defaultTargetID('app');

$app->run();