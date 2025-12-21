<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpSPA\App;
use PhpSPA\Compression\Compressor;

putenv('PHPSPA_COMPRESSION_STRATEGY=fallback');

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

$app->meta(charset: 'UTF-8')
    ->meta(name: 'viewport', content: 'width=device-width, initial-scale=1.0')
    ->link(rel: 'preconnect', content: 'https://fonts.googleapis.com')
    ->link(rel: 'preconnect', content: 'https://fonts.gstatic.com', attributes: ['crossorigin' => ''])
    ->link(rel: 'stylesheet', content: 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

// --- For Production ---
$app->compression(Compressor::LEVEL_EXTREME);

// --- Run the application ---
$app->run();
