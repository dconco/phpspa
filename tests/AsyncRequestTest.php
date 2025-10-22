<?php

/**
 * Async HTTP Client Tests
 * 
 * Tests the async functionality of useFetch with cURL
 *
 * @since v2.0.0
 * @author dconco <concodave@gmail.com>
 */

use function Component\useFetch;

$async_tests_successful = true;

echo "=== Async Request Test ===\n\n";

// Test 1: Simple async request
echo "Test 1: Simple async request\n";
echo "----------------------------\n";

$promise = useFetch('https://jsonplaceholder.typicode.com/users/1')
    ->async()
    ->get();

echo "Request prepared (not executed yet)...\n";
echo "Type of promise: " . get_class($promise) . "\n";

// Simulate doing other work
usleep(100000); // 100ms
echo "Doing other work...\n";

// Wait for response
$response = $promise->wait();

echo "Status: " . $response->status() . "\n";
echo "OK: " . ($response->ok() ? 'true' : 'false') . "\n";

$data = $response->json();
echo "User name: " . ($data['name'] ?? 'N/A') . "\n";

// Validate test 1
if (!$response->ok() || $response->status() !== 200 || empty($data['name'])) {
    echo "❌ Test 1 FAILED\n";
    $async_tests_successful = false;
} else {
    echo "✅ Test 1 PASSED\n";
}
echo "\n";

// Test 2: Multiple async requests
echo "Test 2: Multiple concurrent requests\n";
echo "--------------------------------------\n";

$startTime = microtime(true);

$promise1 = useFetch('https://jsonplaceholder.typicode.com/users/1')
    ->async()->get();

$promise2 = useFetch('https://jsonplaceholder.typicode.com/users/2')
    ->async()->get();

$promise3 = useFetch('https://jsonplaceholder.typicode.com/users/3')
    ->async()->get();

echo "All 3 requests prepared\n";

// Wait for all responses
$response1 = $promise1->wait();
$response2 = $promise2->wait();
$response3 = $promise3->wait();

$endTime = microtime(true);
$totalTime = round(($endTime - $startTime) * 1000);

$user1 = $response1->json();
$user2 = $response2->json();
$user3 = $response3->json();

echo "User 1: " . ($user1['name'] ?? 'N/A') . "\n";
echo "User 2: " . ($user2['name'] ?? 'N/A') . "\n";
echo "User 3: " . ($user3['name'] ?? 'N/A') . "\n";
echo "Total time: {$totalTime}ms\n";

// Validate test 2
if (!$response1->ok() || !$response2->ok() || !$response3->ok() || 
    empty($user1['name']) || empty($user2['name']) || empty($user3['name'])) {
    echo "❌ Test 2 FAILED\n";
    $async_tests_successful = false;
} else {
    echo "✅ Test 2 PASSED\n";
}
echo "\n";

// Test 3: Async with then() callback
echo "Test 3: Using then() callback\n";
echo "-------------------------------\n";

$callbackExecuted = false;

$promise = useFetch('https://jsonplaceholder.typicode.com/posts/1')
    ->async()
    ->get()
    ->then(function($response) use (&$callbackExecuted) {
        $callbackExecuted = true;
        echo "Callback executed!\n";
        echo "Post title: " . ($response->json()['title'] ?? 'N/A') . "\n";
        return $response;
    });

echo "Callback attached (not executed yet): " . ($callbackExecuted ? 'true' : 'false') . "\n";

// Callback executes when wait() is called
$response = $promise->wait();

echo "After wait(), callback executed: " . ($callbackExecuted ? 'true' : 'false') . "\n";

// Validate test 3
if (!$callbackExecuted || !$response->ok() || empty($response->json()['title'])) {
    echo "❌ Test 3 FAILED\n";
    $async_tests_successful = false;
} else {
    echo "✅ Test 3 PASSED\n";
}
echo "\n";

// Test 4: Async with error handling
echo "Test 4: Error handling with async\n";
echo "-----------------------------------\n";

$promise = useFetch('https://invalid-domain-that-does-not-exist-12345.com')
    ->timeout(5)
    ->async()
    ->get();

echo "Request to invalid domain prepared\n";

$response = $promise->wait();

if ($response->failed()) {
    echo "Request failed as expected\n";
    echo "Error: " . $response->error() . "\n";
    echo "✅ Test 4 PASSED\n";
} else {
    echo "Request unexpectedly succeeded\n";
    echo "❌ Test 4 FAILED\n";
    $async_tests_successful = false;
}

echo "\n=== All Async Tests PASSED ===\n";

// Bonus: Test parallel execution performance
echo "\n";
echo "Test 5: Parallel vs Sequential Execution\n";
echo "------------------------------------------\n";

use PhpSPA\Core\Client\AsyncResponse;

// Parallel execution
$startParallel = microtime(true);
$p1 = useFetch('https://jsonplaceholder.typicode.com/users/1')->async()->get();
$p2 = useFetch('https://jsonplaceholder.typicode.com/users/2')->async()->get();
$p3 = useFetch('https://jsonplaceholder.typicode.com/users/3')->async()->get();

[$r1, $r2, $r3] = AsyncResponse::all([$p1, $p2, $p3]);
$parallelTime = round((microtime(true) - $startParallel) * 1000);

echo "Parallel execution: {$parallelTime}ms\n";
echo "User 1: " . $r1->json()['name'] . "\n";
echo "User 2: " . $r2->json()['name'] . "\n";
echo "User 3: " . $r3->json()['name'] . "\n";

if ($r1->ok() && $r2->ok() && $r3->ok()) {
    echo "✅ Test 5 PASSED (Parallel execution works)\n";
} else {
    echo "❌ Test 5 FAILED\n";
    $async_tests_successful = false;
}

echo "\n=== All Async Tests " . ($async_tests_successful ? "PASSED" : "FAILED") . " ===\n";
