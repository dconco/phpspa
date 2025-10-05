<?php

require_once '../vendor/autoload.php';

use phpSPA\App;
use phpSPA\Compression\Compressor;

// Nonce::enable([
//     'script-src' => ["'self'", "https://unpkg.com/phpspa-js"]
// ]);

/* Initialize a new Application  */
$app = (new App(require 'layout/Layout.php'))
    /* Attach and Run Application */

    ->attach(require 'components/Login.php')
    ->attach(require 'components/Timer.php')
    ->attach(require 'components/Counter.php')
    ->attach(require 'components/HomePage.php')

    ->defaultTargetID('app')
    ->defaultToCaseSensitive()

    ->compression(Compressor::LEVEL_NONE, true)

    ->cors()

    ->styleSheet(
        fn () => <<<CSS
        /* Global styles for the application */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .global-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        CSS,
        'global-css'
    )

    ->script(
        fn () => <<<JS
        // Global script - should execute FIRST
        console.log('1. Global script executing first');
        
        phpspa.on("beforeload", ({ route }) => {
            document.getElementById("app").innerHTML = "<h1>Loading...</h1>";
            console.log("Before Load: " + route);
        });
        phpspa.on("load", ({ route, success, error }) => {
            console.log("Loaded!");
            if (!success) {
                console.log('But an error occured: ', error);
            }
        });

        document.onclick = () => {
            console.log('Document clicked!');
        };
        
        // Global utility function
        window.globalUtils = {
            log: (message) => console.log('[Global Utils]:', message),
            formatDate: (date) => new Date(date).toLocaleDateString(),
            executionOrder: []
        };
        
        // Track execution order
        window.globalUtils.executionOrder.push('Global script executed');
        console.log('2. Global utilities initialized');
        
        // Initialize global styles
        document.addEventListener('DOMContentLoaded', () => {
            console.log('3. Global assets loaded successfully!');
            console.log('Execution order:', window.globalUtils.executionOrder);
        });
        JS,
        'global-js'
    )

    ->run();

// --- NOT FOUND ---
echo "404 Not Found";