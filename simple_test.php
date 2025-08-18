<?php

require_once 'vendor/autoload.php';

use phpSPA\Compression\Compressor;
use phpSPA\Core\Utils\HtmlCompressor;

$testJs = "console.log('Before Load: ' + route);\n        if (route && route.length > 0) {\n            document.getElementById('content').innerHTML = 'Loading...';\n        }";
$testHtml = "<script>\n    $testJs\n</script>";

echo "Original HTML with JS:\n";
echo $testHtml . "\n\n";

echo "Basic compression (level " . Compressor::LEVEL_BASIC . "):\n";
HtmlCompressor::setLevel(Compressor::LEVEL_BASIC);
echo HtmlCompressor::compress($testHtml) . "\n\n";

echo "Aggressive compression (level " . Compressor::LEVEL_AGGRESSIVE . "):\n";
HtmlCompressor::setLevel(Compressor::LEVEL_AGGRESSIVE);
echo HtmlCompressor::compress($testHtml) . "\n\n";

echo "Extreme compression (level " . Compressor::LEVEL_EXTREME . "):\n";
HtmlCompressor::setLevel(Compressor::LEVEL_EXTREME);
$extremeResult = HtmlCompressor::compress($testHtml);
echo $extremeResult . "\n\n";

echo "Size comparison:\n";
echo "Original: " . strlen($testHtml) . " bytes\n";
HtmlCompressor::setLevel(Compressor::LEVEL_AGGRESSIVE);
$aggressiveResult = HtmlCompressor::compress($testHtml);
echo "Aggressive: " . strlen($aggressiveResult) . " bytes\n";
echo "Extreme: " . strlen($extremeResult) . " bytes\n";
echo "Extreme saves: " . (strlen($aggressiveResult) - strlen($extremeResult)) . " additional bytes\n";
