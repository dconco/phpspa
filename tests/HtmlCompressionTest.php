<?php

/**
 * HTML Compression Test Suite
 *
 * Tests the HTML compression functionality including multi-level compression,
 * size reduction verification, and compression level effectiveness.
 *
 * @see https://phpspa.tech/v1.1.5/1-compression-system/ Compression System Documentation
 * @since v1.1.5
 * @author dconco <me@dconco.tech>
 */

use PhpSPA\Compression\Compressor;

echo "\n============== HTML COMPRESSION TEST STARTED ==============\n";

$testHtml = file_get_contents(__DIR__ . '/Test.html');

Compressor::setLevel(Compressor::LEVEL_BASIC);
$basic = Compressor::compress($testHtml);

Compressor::setLevel(Compressor::LEVEL_AGGRESSIVE);
$aggressive = Compressor::compress($testHtml);

Compressor::setLevel(Compressor::LEVEL_EXTREME);
$extreme = Compressor::compress($testHtml);

$lenOrig = strlen($testHtml);
$lenBasic = strlen($basic);
$lenAgg = strlen($aggressive);
$lenExt = strlen($extreme);

echo "Original size: {$lenOrig} bytes\n";
echo "Basic compression: {$lenBasic} bytes (" .
	round((($lenOrig - $lenBasic) / $lenOrig) * 100, 1) .
	"% reduction)\n";
echo "Aggressive compression: {$lenAgg} bytes (" .
	round((($lenOrig - $lenAgg) / $lenOrig) * 100, 1) .
	"% reduction)\n";
echo "Extreme compression: {$lenExt} bytes (" .
	round((($lenOrig - $lenExt) / $lenOrig) * 100, 1) .
	"% reduction)\n\n";

$test1_successful = true;

// Only test compression if original content is large enough (>500 bytes)
if ($lenOrig > 500) {
	if ($lenAgg > $lenOrig) {
		echo "FAILED TEST: Aggressive should be <= Original size\n";
		$test1_successful = false;
	}
	if ($lenExt > $lenAgg) {
		echo "FAILED TEST: Extreme should be <= Aggressive size\n";
		$test1_successful = false;
	}
} else {
	echo "SKIPPED: Content too small for meaningful compression testing (need >500 bytes)\n";
	echo "Current content: {$lenOrig} bytes\n";
}

if ($test1_successful) {
	echo "ALL TESTS PASSED\n";
}

echo "\n============== HTML COMPRESSION TESTS COMPLETED ==============\n";
