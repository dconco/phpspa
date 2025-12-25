<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpSPA\App;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Http\Request;
use PhpSPA\Http\Response;

chdir(__DIR__); // --- Change the current working directory to the project root dir ---

// --- Load components ---
require_once 'app/layout/layout.php';
require_once 'app/pages/HomePage.php';
require_once 'app/pages/AboutPage.php';
require_once 'app/pages/DocsPage.php';

// --- Start Application ---

$app = new App($layout);

$app->useModule();
$app->useStatic('/', __DIR__ . '/public/');

// --- Attach components to application ---

$app->attach($homePage);
$app->attach($aboutPage);
$app->attach($docsPage);

// --- Add global meta data to the application ---

$app->meta(charset: 'UTF-8')
    ->meta(name: 'viewport', content: 'width=device-width, initial-scale=1.0')
    ->link(rel: 'preconnect', content: 'https://fonts.googleapis.com')
    ->link(rel: 'preconnect', content: 'https://fonts.gstatic.com', attributes: ['crossorigin' => true])
    ->link(rel: 'stylesheet', content: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap', type: 'text/css')

    ->link(rel: 'shortcut icon', content: '/logo.svg', type: 'image/xml+svg')
    ->link(rel: 'apple-touch-icon', content: '/logo.svg', type: 'image/xml+svg');


// --- For Production ---

if (getenv('APP_ENV') === 'production') {
    $app->compression(Compressor::LEVEL_EXTREME);

    // --- Apply Canonical Link ---
    $req = new HttpRequest();
    $siteURL = getenv('APP_URL') ?: $req->siteURL();

    $app->link(rel: 'canonical', content: $siteURL);
}

// --- Run the application ---
$app->run();

// --- Return 404 error if no route was matched ---
Response::sendError('Page not found', Response::StatusNotFound);