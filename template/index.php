<?php

use PhpSPA\Component;

chdir(__DIR__);
require_once '../vendor/autoload.php';

use PhpSPA\App;
use PhpSPA\Compression\Compressor;

putenv('PHPSPA_COMPRESSION_STRATEGY=native');

/* Initialize a new Application  */
(new App(require 'layout/Layout.php'))
    /* Attach and Run Application */

    ->attach(require 'components/Login.php')
    ->attach(require 'components/Timer.php')
    ->attach(require 'components/Counter.php')
    ->attach(require 'components/HomePage.php')
    ->attach(require 'components/Todo.php')
    ->attach(require 'components/FetchApi.php')

    ->defaultTargetID('app')
    ->defaultToCaseSensitive()

    ->compression(Compressor::LEVEL_EXTREME, true)

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
        'global-css',
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
        'global-js',
    )

    ->run();




// --- APP 2 ---
(new App(fn () => '<div id="root"></div>'))
    ->attach(new Component(fn () => <<<HTML
        <div>
            <h2>Hello This is About Us Page</h2>
            <br />
            <Component.Link to="/feed">Move to News Page</Component.Link>
        </div>
    HTML)->title('App 2 - About Us')->route('/about')->targetID('app'))

    ->attach(new Component(fn () => <<<HTML
        <div>
            <h2>Welcome to News Feed</h2>
            <br />
            <Component.Link to="/">Go back to Home Page</Component.Link>
        </div>
    HTML)->title('App 2 - News Feed')->route('/feed'))

    ->defaultTargetID('root')
    ->run();


// --- NOT FOUND ---
echo "404 Not Found";