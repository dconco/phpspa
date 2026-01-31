<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpSPA\Core\Helper\AssetLinkManager;

// Test through public API only
$link = AssetLinkManager::generateCssLink('/test', 0, 0);
echo "Generated link: $link\n";

// Extract and test resolution
$path = parse_url($link, PHP_URL_PATH);
if (preg_match('/\/phpspa\/assets\/(.+)\.css$/', $path, $matches)) {
    $encodedPart = $matches[1];
    echo "Encoded part from URL: $encodedPart\n";

    // Verify the encoded part contains tildes (our separator)
    if (strpos($encodedPart, '~') !== false) {
        echo "✅ SUCCESS: Tilde separator found in encoded part\n";
    } else {
        echo "❌ FAIL: Tilde separator not found\n";
    }

    // Test resolution
    $resolved = AssetLinkManager::resolveAssetRequest($path);
    if ($resolved) {
        echo "✅ SUCCESS: Asset resolution works!\n";
        echo "Component route: " . $resolved['componentRoute'] . "\n";
        echo "Asset type: " . $resolved['assetType'] . "\n";
        echo "Asset index: " . $resolved['assetIndex'] . "\n";
    } else {
        echo "❌ FAIL: Asset resolution failed\n";
    }
} else {
    echo "❌ FAIL: Could not extract encoded part from URL\n";
}

// Test tampering detection
$tamperedPath = str_replace('.css', 'x.css', $path); // Tamper with the encoded part
try {
    $resolvedTampered = AssetLinkManager::resolveAssetRequest($tamperedPath);
    echo "❌ FAIL: Tampering not detected (function should have exited)\n";
} catch (Exception $e) {
    echo "✅ SUCCESS: Tampering detected (exception thrown)\n";
}

// Test with named asset
$namedLink = AssetLinkManager::generateCssLink('/test', 0, 0, 'mystyle');
echo "\nNamed asset link: $namedLink\n";

$namedPath = parse_url($namedLink, PHP_URL_PATH);
$namedResolved = AssetLinkManager::resolveAssetRequest($namedPath);
if ($namedResolved) {
    echo "✅ SUCCESS: Named asset resolution works!\n";
    echo "Name: " . ($namedResolved['name'] ?? 'null') . "\n";
} else {
    echo "❌ FAIL: Named asset resolution failed\n";
}

echo "\n🎉 All tests completed!\n";