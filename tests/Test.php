<?php

/**
 * Unified Test Runner for PhpSPA v1.1.5
 *
 * Runs all compression and JavaScript ASI tests for the new compression system.
 * This test runner is CLI-only for security and is used in CI/CD workflows.
 *
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

// Initialize session for testing
if (!session_id()) {
    session_start();
}

// define the variables
$test1_successful;
$test2_successful;
$test3_successful;
$todo_tests_successful;
$comprehensive_tests_successful;
$assets_link_tests_successful;
$async_tests_successful;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/HtmlCompressionTest.php';
require_once __DIR__ . '/EnhancedJsCompressionTest.php';  // Use enhanced tests
require_once __DIR__ . '/Utf8IntegrationTest.php';        // UTF-8 encoding and integration tests
require_once __DIR__ . '/TodoJsCompressionTest.php';
require_once __DIR__ . '/ComprehensiveJsCompressionTest.php'; // Comprehensive real-world tests

echo "\n";
echo "================================================================================\n";
echo "                          ASYNC HTTP CLIENT TESTS                              \n";
echo "================================================================================\n";
echo "\n";

// Run Async Request Tests BEFORE AssetLinkTest (which calls exit())
require_once __DIR__ . '/AsyncRequestTest.php';

echo "\n";

// Run AssetLinkTest last since it may call exit()
require_once __DIR__ . '/AssetLinkTest.php';               // Asset link generation and serving tests

$testSuccessful = $test1_successful && $test2_successful && $test3_successful && $todo_tests_successful && $comprehensive_tests_successful && $assets_link_tests_successful && $async_tests_successful;

echo "\n================= COMBINED TESTS RESULT: " . ($testSuccessful ? 'ALL PASSED' : 'SOME FAILED') . " =================\n";
if (!$testSuccessful) {
    exit(1);
}
exit(0);
