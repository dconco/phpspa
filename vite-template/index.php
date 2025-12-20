<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpSPA\App;

// --- Load components ---
require_once 'app/layout/layout.php';
require_once 'app/pages/HomePage.php';
require_once 'app/pages/AboutPage.php';
require_once 'app/pages/DocsPage.php';

// --- Start Application ---

$app = new App($layout);

$app->useModule();
$app->useStatic('/', __DIR__ . '/public/assets');

// --- Attach components to application ---

$app->attach($homePage);
$app->attach($aboutPage);
$app->attach($docsPage);

// --- Run the application ---
$app->run();
