<?php

require '../vendor/autoload.php';

require 'components/HomePage.php';
require 'components/Login.php';
require 'Layout.php';

use phpSPA\App;
use phpSPA\Component;

/* Initialize a new Application */
$app = new App('layout');
$app->targetID('app');

/* Create a new HOME PAGE Component */
$homePage = new Component('HomePage');
$homePage->title = 'Home Page';
$homePage->method = 'GET';
$homePage->route = '/';

/* LOGIN PAGE Component */
$loginPage = new Component('Login');
$loginPage->title = 'Login Page';
$loginPage->method = 'GET|POST';
$loginPage->route = '/login';

/* Attach and Run Application */
$app->attach($homePage);
$app->attach($loginPage);
$app->run();