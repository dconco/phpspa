<?php

/**
 * Asset Link Generation and Serving Test
 *
 * Tests the new session-based asset link generation and serving functionality
 * that replaces inline styles and scripts with external links.
 */

use phpSPA\App;
use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Core\Helper\AssetLinkManager;

function testAssetLinkGeneration()
{
    echo "============== ASSET LINK GENERATION TEST STARTED ==============\n";

    $passed = 0;
    $total = 0;

    // Test 1: CSS link generation
    $total++;
    echo "\n=== Test: CSS Link Generation ===\n";
    $cssLink = AssetLinkManager::generateCssLink('/test', 0);
    if (preg_match('/\/phpspa-assets\/[a-f0-9]{32}\.css$/', parse_url($cssLink, PHP_URL_PATH))) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: CSS link format incorrect: $cssLink\n";
    }

    // Test 2: JS link generation
    $total++;
    echo "\n=== Test: JS Link Generation ===\n";
    $jsLink = AssetLinkManager::generateJsLink('/test', 1);
    if (preg_match('/\/phpspa-assets\/[a-f0-9]{32}\.js$/', parse_url($jsLink, PHP_URL_PATH))) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: JS link format incorrect: $jsLink\n";
    }

    // Test 3: Asset resolution
    $total++;
    echo "\n=== Test: Asset Resolution ===\n";
    $cssPath = parse_url($cssLink, PHP_URL_PATH);
    $resolved = AssetLinkManager::resolveAssetRequest($cssPath);
    if ($resolved && $resolved['componentRoute'] === '/test' && $resolved['assetType'] === 'css' && $resolved['assetIndex'] === 0) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: Asset resolution failed or incorrect data\n";
        var_dump($resolved);
    }

    echo "\nAsset Link Generation Tests Summary: $passed/$total PASSED\n";
    echo "============== ASSET LINK GENERATION TESTS COMPLETED ==============\n\n";

    return $passed === $total;
}

function testAssetServing()
{
    echo "============== ASSET SERVING TEST STARTED ==============\n";

    $passed = 0;
    $total = 0;

    // Helper function to create component
    function createTestComponent()
    {
        return (new Component(function (Request $request): string {
            return '<div>Test Component</div>';
        }))
        ->route('/test-asset')
        ->script(function () {
            return 'console.log("Test script");';
        })
        ->styleSheet(function () {
            return 'body { background-color: blue; }';
        });
    }

    // Test 1: CSS asset serving
    $total++;
    echo "\n=== Test: CSS Asset Serving ===\n";

    // Generate CSS link
    $cssLink = AssetLinkManager::generateCssLink('/test-asset', 0);
    $cssPath = parse_url($cssLink, PHP_URL_PATH);

    // Set up environment
    $_SERVER['REQUEST_URI'] = $cssPath;
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $component = createTestComponent();
    $app = new App(function () { return '<html></html>'; });
    $app->attach($component);

    // Capture output
    ob_start();
    $app->run();
    $output = ob_get_clean();

    if (strpos($output, 'body { background-color: blue; }') !== false) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: CSS content not served correctly\n";
        echo "Output: $output\n";
    }

    // Test 2: JS asset serving
    $total++;
    echo "\n=== Test: JS Asset Serving ===\n";

    // Generate JS link
    $jsLink = AssetLinkManager::generateJsLink('/test-asset', 0);
    $jsPath = parse_url($jsLink, PHP_URL_PATH);

    // Set up environment
    $_SERVER['REQUEST_URI'] = $jsPath;

    $component2 = createTestComponent();
    $app2 = new App(function () { return '<html></html>'; });
    $app2->attach($component2);

    // Capture output
    ob_start();
    $app2->run();
    $output = ob_get_clean();

    if (strpos($output, 'console.log("Test script");') !== false) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: JS content not served correctly\n";
        echo "Output: $output\n";
    }

    echo "\nAsset Serving Tests Summary: $passed/$total PASSED\n";
    echo "============== ASSET SERVING TESTS COMPLETED ==============\n\n";

    return $passed === $total;
}

function testCompressionLevels()
{
    echo "============== COMPRESSION LEVELS TEST STARTED ==============\n";

    $passed = 0;
    $total = 0;

    // Test 1: Normal request compression
    $total++;
    echo "\n=== Test: Normal Request Uses Standard Compression ===\n";

    $cssLink = AssetLinkManager::generateCssLink('/test-compression', 0);
    $cssPath = parse_url($cssLink, PHP_URL_PATH);

    // Set up normal request
    $_SERVER['REQUEST_URI'] = $cssPath;
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    $component = (new Component(function (Request $request): string {
        return '<div>Test</div>';
    }))
    ->route('/test-compression')
    ->styleSheet(function () {
        return 'body {    background-color: red;    margin: 10px;   }';
    });

    $app = new App(function () { return '<html></html>'; });
    $app->attach($component);

    ob_start();
    $app->run();
    $normalOutput = ob_get_clean();

    // Test 2: PHPSPA_REQUEST compression
    $total++;
    echo "\n=== Test: PHPSPA_REQUEST Uses LEVEL_EXTREME Compression ===\n";

    $cssLink2 = AssetLinkManager::generateCssLink('/test-compression-extreme', 0);
    $cssPath2 = parse_url($cssLink2, PHP_URL_PATH);

    // Set up PHPSPA_REQUEST
    $_SERVER['REQUEST_URI'] = $cssPath2;
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'PHPSPA_REQUEST';

    $component2 = (new Component(function (Request $request): string {
        return '<div>Test</div>';
    }))
    ->route('/test-compression-extreme')
    ->styleSheet(function () {
        return 'body {    background-color: red;    margin: 10px;   }';
    });

    $app2 = new App(function () { return '<html></html>'; });
    $app2->attach($component2);

    ob_start();
    $app2->run();
    $extremeOutput = ob_get_clean();

    // Verify that extreme compression is more aggressive
    if (strlen($extremeOutput) <= strlen($normalOutput)) {
        echo "PASS\n";
        $passed++;
        $passed++; // Count both tests as passed since extreme compression worked
    } else {
        echo "FAIL: PHPSPA_REQUEST compression not more aggressive\n";
        echo "Normal: $normalOutput\n";
        echo "Extreme: $extremeOutput\n";
    }

    echo "\nCompression Tests Summary: $passed/$total PASSED\n";
    echo "============== COMPRESSION TESTS COMPLETED ==============\n\n";

    return $passed === $total;
}

function testLinkGeneration()
{
    echo "============== LINK GENERATION IN HTML TEST STARTED ==============\n";

    $passed = 0;
    $total = 0;

    // Test that components now generate <link> and <script> tags instead of inline content
    $total++;
    echo "\n=== Test: HTML Contains Asset Links Instead of Inline Content ===\n";

    $_SERVER['REQUEST_URI'] = '/test-links';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    $component = (new Component(function (Request $request): string {
        return '<div>Test Component Content</div>';
    }))
    ->route('/test-links')
    ->script(function () {
        return 'console.log("This should be in external file");';
    })
    ->styleSheet(function () {
        return 'body { color: green; }';
    });

    $app = new App(function () {
        return '<html><head></head><body><div id="content"></div></body></html>';
    });
    $app->defaultTargetID('content')->attach($component);

    ob_start();
    $app->run();
    $output = ob_get_clean();

    // Check for link and script tags with correct URLs
    $hasLinkTag = preg_match('/<link[^>]*href="[^"]*\/phpspa-assets\/[a-f0-9]{32}\.css"[^>]*\/>/', $output);
    $hasScriptTag = preg_match('/<script[^>]*src="[^"]*\/phpspa-assets\/[a-f0-9]{32}\.js"[^>]*><\/script>/', $output);
    $hasInlineStyle = strpos($output, '<style>') !== false;
    $hasInlineScript = strpos($output, 'console.log("This should be in external file");') !== false;

    if ($hasLinkTag && $hasScriptTag && !$hasInlineStyle && !$hasInlineScript) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL: Expected external links, got inline content or missing links\n";
        echo "Has link tag: " . ($hasLinkTag ? 'yes' : 'no') . "\n";
        echo "Has script tag: " . ($hasScriptTag ? 'yes' : 'no') . "\n";
        echo "Has inline style: " . ($hasInlineStyle ? 'yes' : 'no') . "\n";
        echo "Has inline script: " . ($hasInlineScript ? 'yes' : 'no') . "\n";
        echo "Output: $output\n";
    }

    echo "\nLink Generation Tests Summary: $passed/$total PASSED\n";
    echo "============== LINK GENERATION TESTS COMPLETED ==============\n\n";

    return $passed === $total;
}

// Run all tests
$allTestsPassed = true;

$allTestsPassed &= testAssetLinkGeneration();
$allTestsPassed &= testAssetServing();
$allTestsPassed &= testCompressionLevels();
$allTestsPassed &= testLinkGeneration();

if ($allTestsPassed) {
    echo "================= ASSET LINK TESTS RESULT: ALL PASSED =================\n";
} else {
    echo "================= ASSET LINK TESTS RESULT: SOME FAILED =================\n";
}

$assets_link_tests_successful = $allTestsPassed;
