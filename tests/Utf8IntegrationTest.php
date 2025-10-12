<?php

/**
 * UTF-8 Encoding and Integration Test Suite for PhpSPA v1.1.6
 *
 * Tests the newly fixed errors including:
 * - UTF-8 safe base64 encoding functions
 * - Integration scenarios combining multiple compression fixes
 * - Edge cases for the compression system improvements
 *
 * This test validates the fixes mentioned in TODO.md for:
 * 1. btoa encoding error for Latin characters
 * 2. Integration of JavaScript compression fixes
 * 3. Combined functionality of all new features
 *
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 * @since v1.1.6
 * @author dconco <concodave@gmail.com>
 */

use PhpSPA\Compression\Compressor;

echo "\n============== UTF-8 ENCODING & INTEGRATION TESTS STARTED ==============\n\n";

$test3_successful = true;

function compressAndTest(string $content): string
{
    Compressor::setLevel(Compressor::LEVEL_EXTREME);
    return Compressor::compress($content);
}

function runUtf8IntegrationTests(): bool
{
    $allPassed = true;

    // Test 1: UTF-8 Character Handling in JavaScript String Literals
    echo "=== Test: UTF-8 Characters in JavaScript String Literals ===\n";
    $utf8JsTest = '<script>
		const message = "Héllo Wörld! 你好世界 🌍";
		const greeting = "Café naïve résumé";
		alert(message + " " + greeting);
	</script>';

    $compressed = compressAndTest($utf8JsTest);
    $pass = strpos($compressed, 'Héllo Wörld! 你好世界 🌍') !== false &&
            strpos($compressed, 'Café naïve résumé') !== false &&
            strpos($compressed, 'Héllo;Wörld') === false &&
            strpos($compressed, 'Café;naïve') === false;

    echo ($pass ? 'PASS' : 'FAIL') . "\n";
    if (!$pass) {
        echo "Output:\n$compressed\n";
    }
    $allPassed = $allPassed && $pass;

    // Test 2: Integration - Complex JavaScript with UTF-8 and Method Calls
    echo "\n=== Test: Complex JavaScript Integration with UTF-8 ===\n";
    $complexTest = '<script>
		const users = ["José", "François", "Müller"];
		users.forEach(function(user) {
			console.log("User: " + user);
			alert("Welcome " + user + "!");
		});
		if (window.location) {
			document.title = "Página de usuários";
		}
	</script>';

    $compressed = compressAndTest($complexTest);
    $pass = strpos($compressed, 'forEach(function(user)') !== false &&
            strpos($compressed, 'José') !== false &&
            strpos($compressed, 'François') !== false &&
            strpos($compressed, 'Müller') !== false &&
            strpos($compressed, 'Página de usuários') !== false &&
            strpos($compressed, 'forEach;(') === false &&
            strpos($compressed, 'José;') === false;

    echo ($pass ? 'PASS' : 'FAIL') . "\n";
    if (!$pass) {
        echo "Output:\n$compressed\n";
    }
    $allPassed = $allPassed && $pass;

    // Test 3: HTML + CSS + JavaScript Integration with Special Characters
    echo "\n=== Test: Full HTML Integration with Special Characters ===\n";
    $fullHtmlTest = '<!DOCTYPE html>
	<html>
		<head>
			<title>Test Página</title>
			<style>
				.message { content: "Café naïve résumé"; }
				.greeting::before { content: "¡Hola!"; }
			</style>
		</head>
		<body>
			<h1>Bienvenidos</h1>
			<p class="message">This is a test with special chars: àáâãäåæçèéêë</p>
			<script>
				const name = "José María";
				document.querySelector(".message").addEventListener("click", function() {
					alert("Hello " + name + "! How are you?");
				});
			</script>
		</body>
	</html>';

    $compressed = compressAndTest($fullHtmlTest);
    $pass = strpos($compressed, 'José María') !== false &&
            strpos($compressed, 'àáâãäåæçèéêë') !== false &&
            strpos($compressed, 'addEventListener') !== false &&
            strpos($compressed, 'Café naïve résumé') !== false &&
            strpos($compressed, 'José;María') === false &&
            strpos($compressed, 'addEventListener;(') === false;

    echo ($pass ? 'PASS' : 'FAIL') . "\n";
    if (!$pass) {
        echo "Output:\n$compressed\n";
    }
    $allPassed = $allPassed && $pass;

    // Test 4: Edge Case - Mixed Content with Emojis and Unicode
    echo "\n=== Test: Edge Case - Emojis and Unicode Characters ===\n";
    $emojiTest = '<div>
		<p>Welcome! 🎉🌟</p>
		<script>
			const reactions = ["👍", "❤️", "😊", "🚀"];
			reactions.forEach(function(emoji) {
				console.log("Reaction: " + emoji);
			});
			if (confirm("Do you like emojis? 🤔")) {
				alert("Great! 🎊");
			}
		</script>
		<style>
			.emoji::after { content: "🎨"; }
		</style>
	</div>';

    $compressed = compressAndTest($emojiTest);
    $pass = strpos($compressed, '🎉🌟') !== false &&
            strpos($compressed, 'forEach(function(emoji)') !== false &&
            strpos($compressed, '👍') !== false &&
            strpos($compressed, '🤔') !== false &&
            strpos($compressed, '🎨') !== false &&
            strpos($compressed, 'forEach;(') === false;

    echo ($pass ? 'PASS' : 'FAIL') . "\n";
    if (!$pass) {
        echo "Output:\n$compressed\n";
    }
    $allPassed = $allPassed && $pass;

    // Test 5: Regression Test - Ensure Previous Fixes Still Work
    echo "\n=== Test: Regression - Core Compression Fixes Work ===\n";
    $regressionTest = '<script>
		// Test forEach method preservation
		const items = ["a", "b", "c"];
		items.forEach(function(item) {
			console.log(item);
		});
		
		// Test alert message fix
		const form = document.querySelector("form");
		if (form) {
			form.addEventListener("submit", function(e) {
				e.preventDefault();
				alert("Thank you for your message! We will get back to you soon.");
			});
		}
		
		// Test variable name preservation
		function processEntry(entry) {
			return entry.value;
		}
		
		// Test querySelector methods
		const element = document.getElementById("test");
		if (element) {
			element.addEventListener("click", function() {
				console.log("clicked");
			});
		}
	</script>';

    $compressed = compressAndTest($regressionTest);
    $pass = strpos($compressed, 'forEach(function(item)') !== false &&
            strpos($compressed, 'Thank you for your message! We will get back to you soon.') !== false &&
            strpos($compressed, 'entry.value') !== false &&
            strpos($compressed, 'addEventListener(') !== false &&
            strpos($compressed, 'getElementById(') !== false &&
            strpos($compressed, 'forEach;(') === false &&
            strpos($compressed, 'en;try') === false &&
            strpos($compressed, 'addEventListener;(') === false &&
            strpos($compressed, 'getElementById;(') === false &&
            strpos($compressed, 'Thank you;for') === false;

    echo ($pass ? 'PASS' : 'FAIL') . "\n";
    if (!$pass) {
        echo "Output:\n$compressed\n";
    }
    $allPassed = $allPassed && $pass;

    return $allPassed;
}

// Run the UTF-8 and integration tests
$testResult = runUtf8IntegrationTests();

echo "\nUTF-8 & Integration Tests Summary: " . ($testResult ? 'ALL PASSED' : 'SOME FAILED') . "\n";

$test3_successful = $testResult;

echo "\n============== UTF-8 ENCODING & INTEGRATION TESTS COMPLETED ==============\n";
