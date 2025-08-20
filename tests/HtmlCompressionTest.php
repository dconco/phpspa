<?php

/**
 * HTML Compression Test Suite
 *
 * Tests the HTML compression functionality including multi-level compression,
 * size reduction verification, and compression level effectiveness.
 *
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 * @since v1.1.5
 * @author dconco <concodave@gmail.com>
 */

use phpSPA\Compression\Compressor;

echo "\n================ HTML COMPRESSION TEST STARTED ==================\n\n";

$testJs = "console.log('Before Load: ' + route);\n        if (route && route.length > 0) {\n            document.getElementById('content').innerHTML = 'Loading...';\n        }";
$testHtml = "<script>\n    $testJs\n</script>";

Compressor::setLevel(Compressor::LEVEL_BASIC);
$basic = Compressor::compress($testHtml);

Compressor::setLevel(Compressor::LEVEL_AGGRESSIVE);
$aggressive = Compressor::compress($testHtml);

Compressor::setLevel(Compressor::LEVEL_EXTREME);
$extreme = Compressor::compress($testHtml);

$lenOrig = strlen($testHtml);
$lenAgg = strlen($aggressive);
$lenExt = strlen($extreme);

$test1_successful = true;

if ($lenAgg > $lenOrig) {
    echo "FAILED TEST: Aggressive should be <= Original size\n";
    $test1_successful = false;
}
if ($lenExt > $lenAgg) {
    echo "FAILED TEST: Extreme should be <= Aggressive size\n";
    $test1_successful = false;
}

if ($test1_successful) {
    echo "ALL TESTS PASSED\n";
}

echo "\n================ HTML COMPRESSION TESTS COMPLETED ==================\n";
