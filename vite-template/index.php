<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpSPA\App;
use PhpSPA\Compression\Compressor;

chdir(__DIR__);

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
    ->link(rel: 'preconnect', content: 'https://fonts.gstatic.com', attributes: ['crossorigin' => ''])
    ->link(rel: 'stylesheet', content: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap', type: 'text/css')

    ->link(rel: 'shortcut icon', content: '/assets/logo.svg', type: 'image/xml+svg')
    ->link(rel: 'apple-touch-icon', content: '/assets/logo.svg', type: 'image/xml+svg');

// --- For Production ---
if (getenv('APP_ENV') === 'production') {
    $app->compression(Compressor::LEVEL_EXTREME);

    // --- Apply Canonical Link ---
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    $baseUrl = rtrim(getenv('APP_URL') ?: "$scheme://$host", '/');
    $canonicalUrl = $baseUrl . $path;

    $app->link(rel: 'canonical', content: $canonicalUrl);
}

// --- Run the application ---
$app->run();
