<?php

require '../vendor/autoload.php';

require 'components/HomePage.php';
require 'components/Login.php';
require 'Layout.php';

use phpSPA\App;
use phpSPA\Component;
use phpSPA\Http\Request;

/* Initialize a new Application */
$app = new App('layout');
$app->defaultTargetID('app');

/* Create a new HOME PAGE Component */
$homePage = (new Component('HomePage'))
   ->title('Home Page')
   ->method('GET')
   ->route('/phpspa/template/{id}');

/* LOGIN PAGE Component */
$loginPage = (new Component('Login'))
   ->title('Login Page')
   ->method('GET|POST')
   ->route('/login');

/* Attach and Run Application */
$app->attach($homePage);
$app->attach($loginPage);
$app->run();