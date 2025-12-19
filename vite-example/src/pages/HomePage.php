<?php

use PhpSPA\Component;

$homePage = new Component(fn() => <<<HTML
    <div class="page">
        <h1>Welcome to PhpSPA + Vite</h1>
        <p>This is a modern development setup using:</p>
        <ul>
            <li>PhpSPA for backend SPA routing</li>
            <li>Vite for frontend build tooling</li>
            <li>@dconco/phpspa npm package</li>
        </ul>
        <p>Check the browser console to see PhpSPA in action!</p>
    </div>
HTML);

$homePage->route('/');
