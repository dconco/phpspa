<?php

/**
 * Enhanced JavaScript Compression Tests for phpSPA v1.1.6
 *
 * Tests the corrected JavaScript minification functionality focusing on:
 * - String literal preservation
 * - Method call preservation
 * - Variable name integrity
 * - Functional JavaScript output
 *
 * @since v1.1.6
 * @author dconco <concodave@gmail.com>
 */

use phpSPA\Compression\Compressor;

echo "\n============== ENHANCED JS COMPRESSION TEST STARTED ==============\n\n";

$test2_successful = true;

function compressJs(string $js): string
{
    Compressor::setLevel(Compressor::LEVEL_EXTREME);
    $input = '<script>' . $js . '</script>';
    $result = Compressor::compress($input);

    // Extract just the JS content from the script tag
    preg_match('/<script>(.*?)<\/script>/s', $result, $matches);
    return $matches[1] ?? $result;
}

function run_enhanced_js_tests(): bool
{
    $tests = [
        [
            'name' => 'String literals must be preserved exactly',
            'js' => "alert('Hello world! This is a test.')",
            'mustContain' => ['Hello world! This is a test.'],
            'mustNotContain' => ['Hello;world', 'This;is'],
        ],
        [
            'name' => 'Method calls should not be broken',
            'js' => "arr.forEach(function(item) { console.log(item); })",
            'mustContain' => ['forEach(function'],
            'mustNotContain' => ['forEach;('],
        ],
        [
            'name' => 'Variable names should not be corrupted',
            'js' => "function test(entry) { return entry.value; }",
            'mustContain' => ['entry'],
            'mustNotContain' => ['en;try', 'e;try'],
        ],
        [
            'name' => 'Method chaining should be preserved',
            'js' => "data.map(x => x * 2).filter(x => x > 10)",
            'mustContain' => [').filter('],
            'mustNotContain' => [');filter(', 'map;('],
        ],
        [
            'name' => 'Common patterns should work correctly',
            'js' => "document.querySelector('form').addEventListener('submit', function(e) { e.preventDefault(); })",
            'mustContain' => ['querySelector(', 'addEventListener('],
            'mustNotContain' => ['querySelector;(', 'addEventListener;('],
        ],
        [
            'name' => 'Basic semicolon insertion should still work when needed',
            'js' => "let x = 1\nconst y = 2",
            'mustContain' => ['=1; const'],
            'mustNotContain' => ['=1const'],
        ],
        [
            'name' => 'Template literals should be preserved',
            'js' => "console.log(`Hello \${name}, how are you?`)",
            'mustContain' => ['Hello ${name}, how are you?'],
            'mustNotContain' => ['Hello;${', 'how;are'],
        ],
        [
            'name' => 'Complex real-world example should compress correctly',
            'js' => "const observer = new IntersectionObserver(function(entries) { entries.forEach(function(entry) { if (entry.isIntersecting) { entry.target.classList.add('visible'); } }); });",
            'mustContain' => ['forEach(function', 'entry.isIntersecting', 'entry.target'],
            'mustNotContain' => ['forEach;(', 'en;try', 'isIntersecting;'],
        ],
    ];

    $allPassed = true;
    foreach ($tests as $t) {
        $out = compressJs($t['js']);
        $pass = true;
        foreach ($t['mustContain'] as $needle) {
            if (strpos($out, $needle) === false) {
                $pass = false;
                break;
            }
        }
        foreach ($t['mustNotContain'] as $needle) {
            if (strpos($out, $needle) !== false) {
                $pass = false;
                break;
            }
        }

        echo "=== Test: {$t['name']} ===\n";
        echo ($pass ? 'PASS' : 'FAIL') . "\n";
        if (!$pass) {
            echo "Input: {$t['js']}\n";
            echo "Output: $out\n";
            echo "Expected to contain: " . implode(', ', $t['mustContain']) . "\n";
            echo "Expected NOT to contain: " . implode(', ', $t['mustNotContain']) . "\n\n";
        }
        $allPassed = $allPassed && $pass;
    }

    echo "\nSummary: " . ($allPassed ? 'ALL PASSED' : 'SOME FAILED') . "\n";
    return $allPassed;
}

$test2_successful = run_enhanced_js_tests();

echo "\n============== ENHANCED JS COMPRESSION TESTS COMPLETED ==============\n";
