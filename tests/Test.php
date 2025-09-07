<?php

/**
 * Unified Test Runner for phpSPA v1.1.5
 *
 * Runs all compression and JavaScript ASI tests for the new compression system.
 * This test runner is CLI-only for security and is used in CI/CD workflows.
 *
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 * @since v1.1.5
 * @author dconco <concodave@gmail.com>
 */

// Ensure this test runner only executes from the command line (for CI/workflows)
if (php_sapi_name() !== 'cli') {
    // Avoid executing in web contexts
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo "This test runner is CLI-only.\n";
    exit(0);
}

// define the variables
$test1_successful;
$test2_successful;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/HtmlCompressionTest.php';
require_once __DIR__ . '/JsCompressionTest.php';

$testSuccessful = $test1_successful && $test2_successful;

echo "\n================= COMBINED TESTS RESULT: " . ($testSuccessful ? 'ALL PASSED' : 'SOME FAILED') . " =================\n";
if (!$testSuccessful) {
    exit(1);
}
exit(0);
