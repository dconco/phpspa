<?php

use PhpSPA\Component;

$aboutPage = new Component(fn() => <<<HTML
    <div class="page">
        <h1>About This Example</h1>
        <p>This demonstrates the recommended workflow for using PhpSPA:</p>
        <ol>
            <li>Write your frontend code in <code>frontend/src/</code></li>
            <li>Import <code>@dconco/phpspa</code> as a normal npm package</li>
            <li>Run <code>npm run build</code> to bundle everything</li>
            <li>Vite outputs to <code>public/assets/</code></li>
            <li>Reference the built assets in your layout</li>
        </ol>
        <h2>Benefits</h2>
        <ul>
            <li>✅ Hot Module Replacement during development</li>
            <li>✅ TypeScript support out of the box</li>
            <li>✅ Tree-shaking and optimization</li>
            <li>✅ Standard tooling developers already know</li>
            <li>✅ No complex PhpSPA asset configuration</li>
        </ul>
    </div>
HTML);

$aboutPage->route('/about');
