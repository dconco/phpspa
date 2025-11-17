<?php

/**
 * Test specific JavaScript compression issues mentioned in TODO.md
 */

use PhpSPA\Compression\Compressor;

echo "\n============== TODO SPECIFIC JS COMPRESSION TESTS ==============\n\n";

function compressJsForTest(string $js): string
{
    return Compressor::compressWithLevel($js, Compressor::LEVEL_EXTREME, 'JS');
}

function runTodoTests(): bool
{
    $tests = [
        [
            'name' => 'TODO: IntersectionObserver should NOT break forEach method calls',
            'js' => "const observer = new IntersectionObserver(function(entries) {\n  entries.forEach(function(entry) {\n        if (entry.isIntersecting) {\n           entry.target.classList.add('fade-in');\n        }\n  });\n}, observerOptions);",
            'mustContain' => ['entries.forEach(function(entry)'],
            'mustNotContain' => ['forEach;(', 'en;try'],
        ],
        [
            'name' => 'TODO: Alert message should NOT be corrupted with semicolons',
            'js' => "const form = document.querySelector('form');\nif (form) {\n   form.addEventListener('submit', function(e) {\n         e.preventDefault();\n         alert('Thank you for your message! We will get back to you soon.');\n   });\n}",
            'mustContain' => ['Thank you for your message! We will get back to you soon.'],
            'mustNotContain' => ['Thank you;for'],
        ],
        [
            'name' => 'TODO: Variable names should NOT be corrupted',
            'js' => "function(entry) { return entry.value; }",
            'mustContain' => ['entry'],
            'mustNotContain' => ['en;try', 'e;try'],
        ],
        [
            'name' => 'Method chaining should work correctly',
            'js' => "arr.map(x => x * 2).filter(x => x > 10)",
            'mustContain' => [').filter('],
            'mustNotContain' => [');filter(', 'map;('],
        ],
    ];

    $allPassed = true;
    foreach ($tests as $t) {
        $out = compressJsForTest($t['js']);
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
        }
        $allPassed = $allPassed && $pass;
    }

    echo "\nTODO Tests Summary: " . ($allPassed ? 'ALL PASSED' : 'SOME FAILED') . "\n";
    return $allPassed;
}

$todo_tests_successful = runTodoTests();

echo "\n============== TODO SPECIFIC JS COMPRESSION TESTS COMPLETED ==============\n";
