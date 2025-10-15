<?php

/**
 * Comprehensive JavaScript Compression Test Suite
 *
 * Tests real-world JavaScript minification scenarios including the specific
 * issues mentioned in TODO item 6 and ensures proper ASI (Automatic Semicolon Insertion).
 *
 * @since v1.1.6
 * @author dconco <concodave@gmail.com>
 */

echo "\n============== COMPREHENSIVE JS COMPRESSION TEST STARTED ==============\n\n";

$test_successful = true;

function runComprehensiveTests(): bool
{
    $tests = [
        [
            'name' => 'TODO Item 6: IntersectionObserver constructor should NOT break',
            'description' => 'Tests the specific issue from TODO where IntersectionObserver was broken',
            'js' => "const observer = new IntersectionObserver(function(entries) {
	entries.forEach(function(entry) {
		if (entry.isIntersecting) {
			entry.target.classList.add('fade-in');
		}
	});
}, observerOptions);",
            'mustContain' => ['IntersectionObserver(function'],
            'mustNotContain' => ['IntersectionObserver;(function'],
        ],
        [
            'name' => 'Arrow function constructors should work',
            'description' => 'Similar pattern with arrow functions',
            'js' => "const observer = new MutationObserver((mutations) => {
	mutations.forEach(mutation => console.log(mutation));
});",
            'mustContain' => ['MutationObserver((mutations)'],
            'mustNotContain' => ['MutationObserver;('],
        ],
        [
            'name' => 'Basic statement separation with semicolons',
            'description' => 'Newlines between statements should get semicolons',
            'js' => "let a = 1\nlet b = 2\nlet c = 3",
            'mustContain' => ['=1; let', '=2; let'],
            'mustNotContain' => ['=1let', '=2let'],
        ],
        [
            'name' => 'Function calls followed by statements',
            'description' => 'Function calls on separate lines should get semicolons',
            'js' => "doSomething()\nconst result = getValue()\nprocessResult(result)",
            'mustContain' => ['(); const', '(); processResult'],
            'mustNotContain' => ['()const', '()processResult'],
        ],
        [
            'name' => 'Control flow should not get extra semicolons',
            'description' => 'else, catch, finally should not get preceding semicolons',
            'js' => "if (x) {\n  a()\n} else {\n  b()\n}\ntry {\n  c()\n} catch (e) {\n  d()\n} finally {\n  e()\n}",
            'mustContain' => ['} else {', '} catch (', '} finally {'],
            'mustNotContain' => ['};else{', '};catch(', '};finally{'],
        ],
        [
            'name' => 'Do-while loops should not break',
            'description' => 'while in do-while should not get a preceding semicolon',
            'js' => "do {\n  something()\n} while (condition)",
            'mustContain' => ['} while ('],
            'mustNotContain' => ['};while('],
        ],
        [
            'name' => 'String literals should be completely preserved',
            'description' => 'No semicolons should be inserted inside strings',
            'js' => "alert('Hello world! This is a test message.')\nconsole.log('Another message')",
            'mustContain' => ['Hello world! This is a test message.', 'Another message'],
            'mustNotContain' => ['Hello;world', 'is;a;test', 'Another;message'],
        ],
        [
            'name' => 'Complex real-world JavaScript pattern',
            'description' => 'Tests a realistic JavaScript snippet',
            'js' => "document.addEventListener('DOMContentLoaded', function() {
	const form = document.querySelector('#contact-form')
	if (form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault()
			const data = new FormData(form)
			fetch('/api/contact', {
				method: 'POST',
				body: data
			})
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					alert('Thank you for your message!')
				}
			})
		})
	}
})",
            'mustContain' => ['document.addEventListener(', 'querySelector(', 'FormData(', 'preventDefault()', 'fetch('],
            'mustNotContain' => ['addEventListener;(', 'querySelector;(', 'FormData;(', 'preventDefault;()', 'fetch;('],
        ],
        [
            'name' => 'Template literals should be preserved',
            'description' => 'Template literals with expressions should not break',
            'js' => "const message = `Hello \${name}, welcome to our site!`\nconsole.log(message)",
            'mustContain' => ['Hello ${name}, welcome to our site!'],
            'mustNotContain' => ['Hello;${', 'welcome;to'],
        ],
        [
            'name' => 'IIFE patterns should get proper semicolons',
            'description' => 'Immediately Invoked Function Expressions need semicolons before them',
            'js' => "let x = 1\n(function() { console.log('IIFE') })()\nlet y = 2",
            'mustContain' => ['=1;(function', '(); let'],
            'mustNotContain' => ['=1(function', '()let'],
        ],
    ];

    $allPassed = true;
    foreach ($tests as $test) {
        $output = compressJs($test['js']);
        $pass = true;

        // Check mustContain patterns
        foreach ($test['mustContain'] as $needle) {
            if (strpos($output, $needle) === false) {
                $pass = false;
                break;
            }
        }

        // Check mustNotContain patterns
        if ($pass) {
            foreach ($test['mustNotContain'] as $needle) {
                if (strpos($output, $needle) !== false) {
                    $pass = false;
                    break;
                }
            }
        }

        echo "\n=== Test: {$test['name']} ===\n";
        echo "Description: {$test['description']}\n";
        echo ($pass ? 'PASS' : 'FAIL') . "\n";

        if (!$pass) {
            echo "Original:\n{$test['js']}\n";
            echo "Output:\n$output\n";
            echo "Expected to contain: " . implode(', ', $test['mustContain']) . "\n";
            echo "Expected NOT to contain: " . implode(', ', $test['mustNotContain']) . "\n";
        }

        $allPassed = $allPassed && $pass;
    }

    echo "\nSummary: " . ($allPassed ? 'ALL PASSED' : 'SOME FAILED') . "\n";
    return $allPassed;
}

$comprehensive_tests_successful = runComprehensiveTests();

echo "\n============== COMPREHENSIVE JS COMPRESSION TESTS COMPLETED ==============\n";
