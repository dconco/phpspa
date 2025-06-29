<?php

require_once '../vendor/autoload.php';

use phpSPA\App;

/* Initialize a new Application */
$app = new App(require 'Layout.php');

/* Attach and Run Application */
$app->attach(require 'components/HomePage.php');
$app->attach(require 'components/Login.php');
$app->attach(require 'components/Timer.php');
$app->attach(require 'components/Counter.php');

$app->defaultToCaseSensitive();
$app->defaultTargetID('app');

$app->cors([
	/*
	 * Specific domains that are allowed to access your resources.
	 */
	'allow_origin' => '*',

	/*
	 * The HTTP methods that are allowed for CORS requests.
	 */
	'allow_methods' => [
		'GET',
		'POST',
		'PUT',
		'PATCH',
		'DELETE',
		'OPTIONS',
		'PHPSPA_GET',
	],

	/*
	 * Headers that are allowed in CORS requests.
	 */
	'allow_headers' => [
		'Content-Type',
		'Authorization',
		'Origin',
		'Accept',
		'X-CSRF-Token',
	],

	/*
	 * Headers that browsers are allowed to access.
	 */
	'expose_headers' => ['Content-Length', 'Content-Range', 'X-Custom-Header'],

	/*
	 * The maximum time (in seconds) the results of a preflight request can be cached.
	 */
	'max_age' => 3600,

	/*
	 * Indicates whether the request can include user credentials.
	 */
	'allow_credentials' => true,

	/*
	 * Another toggle for allowing credentials, ensuring clarity.
	 */
	'supports_credentials' => true,
]);

$app->run();
