<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpSPA\App;

// --- Load components ---
require_once 'app/layout/layout.php';
require_once 'app/pages/HomePage.php';
require_once 'app/pages/AboutPage.php';

// --- Start Application ---

$app = new App($layout);

$app->useModule();

// --- Attach components to application ---

$app->attach($homePage);
$app->attach($aboutPage);

// --- Run the application ---
$app->run();
