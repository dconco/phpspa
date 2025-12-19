<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpSPA\App;

// --- Load components ---
require_once 'src/layout/layout.php';
require_once 'src/pages/HomePage.php';
require_once 'src/pages/AboutPage.php';

// --- Start Application ---

$app = new App($layout);

$app->useModule();

// --- Attach components to application ---

$app->attach($homePage);
$app->attach($aboutPage);

// --- Run the application ---
$app->run();
