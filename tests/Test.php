<?php

// Ensure this test runner only executes from the command line (for CI/workflows)
if (php_sapi_name() !== 'cli') {
    // Avoid executing in web contexts
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo "This test runner is CLI-only.\n";
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/HtmlCompressionTest.php';
require_once __DIR__ . '/JsCompressionTest.php';

// Ensure test result variables are defined
if (!isset($test1_successful)) $test1_successful = false;
if (!isset($test2_successful)) $test2_successful = false;
$testSuccessful = $test1_successful && $test2_successful;

echo "\n================= COMBINED TESTS RESULT: " . ($testSuccessful ? 'ALL PASSED' : 'SOME FAILED') . " =================\n";
if (!$testSuccessful) {
    exit(1);
}
exit(0);
