<?php

namespace phpSPA\Core\Utils;

use phpSPA\Compression\Compressor;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for phpSPA
 * to reduce payload sizes and improve performance.
 *
 * @package phpSPA\Core\Utils
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 */
trait HtmlCompressor
{
	/**
	 * Current compression level
	 *
	 * @var int
	 */
	private static int $compressionLevel = 1; // Default to LEVEL_AUTO

	/**
	 * Whether to use gzip compression
	 *
	 * @var bool
	 */
	private static bool $useGzip = true;

	/**
	 * Set compression level
	 *
	 * @param int $level Compression level (0-4)
	 * @return void
	 */
	public static function setLevel(int $level): void
	{
		self::$compressionLevel = max(0, min(4, $level));
	}

	/**
	 * Enable or disable gzip compression
	 *
	 * @param bool $enabled Whether to use gzip
	 * @return void
	 */
	public static function setGzipEnabled(bool $enabled): void
	{
		self::$useGzip = $enabled;
	}

	/**
	 * Compress HTML content
	 *
	 * @param string $html HTML content to compress
	 * @return string Compressed HTML
	 */
	public static function compress(
		string $html,
		?string $contentType = null,
	): string {
		if (self::$compressionLevel === Compressor::LEVEL_NONE) {
			if (!headers_sent()) {
				header('Content-Length: ' . strlen($html));

				if ($contentType !== null) {
					header("Content-Type: $contentType; charset=UTF-8");
				}
			}

			return $html;
		}

		$comment = "<!--
  🧩 phpSPA Engine - Minified Output

  This HTML has been automatically minified by the phpSPA runtime engine:
  • Whitespace removed for faster loading
  • Comments stripped (except this one)
  • Attributes optimized for minimal size
  • Performance-optimized for production

  Original source: Component-based PHP library with natural HTML syntax
  Learn More: https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system
-->\n";

		// Apply minification based on compression level
		$html = self::minify($html, self::$compressionLevel);

		// Append Comments
		$html = $comment . $html;

		// Apply gzip compression if enabled and supported
		$html = self::gzipCompress($html, $contentType);

		return $html;
	}

	/**
	 * Minify HTML content
	 *
	 * @param string $html HTML content
	 * @param int $level Compression level
	 * @return string Minified HTML
	 */
	private static function minify(string $html, int $level): string
	{
		if ($level === Compressor::LEVEL_AUTO) {
			$level = self::detectOptimalLevel($html);
		}

		switch ($level) {
			case Compressor::LEVEL_BASIC:
				return self::basicMinify($html);

			case Compressor::LEVEL_AGGRESSIVE:
				return self::aggressiveMinify($html);

			case Compressor::LEVEL_EXTREME:
				return self::extremeMinify($html);

			default:
				return $html;
		}
	}

	/**
	 * Basic HTML minification
	 *
	 * @param string $html HTML content
	 * @return string Minified HTML
	 */
	private static function basicMinify(string $html): string
	{
		// Remove HTML comments (but preserve IE conditional comments)
		$html = preg_replace(
			'/<!--(?!\s*(?:\[if\s|<!|>))(?:(?!-->).)*.?-->/s',
			'',
			$html,
		);

		// Remove extra whitespace between tags
		$html = preg_replace('/>\s+</', '><', $html);

		// Remove leading/trailing whitespace from lines
		$html = preg_replace('/^\s+|\s+$/m', '', $html);

		// Collapse multiple whitespace characters into single space,
		// but preserve newlines inside script and style tags for later processing
		$html = preg_replace_callback(
			'/(<script[^>]*>)(.*?)(<\/script>)|(<style[^>]*>)(.*?)(<\/style>)|(\s+)/s',
			function ($matches) {
				if (isset($matches[1]) && isset($matches[2]) && isset($matches[3])) {
					// This is a script tag - preserve newlines in the content
					return $matches[1] . $matches[2] . $matches[3];
				} elseif (isset($matches[4]) && isset($matches[5]) && isset($matches[6])) {
					// This is a style tag - preserve newlines in the content
					return $matches[4] . $matches[5] . $matches[6];
				} else {
					// This is regular whitespace - collapse to single space
					return ' ';
				}
			},
			$html
		);

		return trim($html);
	}

	/**
	 * Aggressive HTML minification
	 *
	 * @param string $html HTML content
	 * @return string Minified HTML
	 */
	private static function aggressiveMinify(string $html): string
	{
		$html = self::basicMinify($html);

		// Remove whitespace around block elements
		$blockElements =
			'div|p|h[1-6]|ul|ol|li|table|thead|tbody|tr|td|th|form|fieldset|nav|header|footer|section|article|aside|main';
		$html = preg_replace(
			'/\s*(<(?:\/?' . $blockElements . ')[^>]*>)\s*/',
			'$1',
			$html,
		);

		// Remove empty attributes (but preserve required ones)
		$html = preg_replace('/\s(class|id|style)=""\s*/', ' ', $html);

		// Remove unnecessary quotes from attributes (simple values only)
		$html = preg_replace(
			'/\s([a-zA-Z-]+)="([a-zA-Z0-9-_\.]+)"\s*/',
			' $1=$2 ',
			$html,
		);

		// Minify JavaScript inside script tags
		$html = preg_replace_callback(
			'/<script[^>]*>(.*?)<\/script>/si',
			function ($matches) {
				$scriptTag = $matches[0];
				$jsContent = $matches[1];

				// Only minify if it's not an external script (has content)
				if (trim($jsContent)) {
					$minifiedJs = self::minifyJavaScript($jsContent);
					return str_replace($jsContent, $minifiedJs, $scriptTag);
				}

				return $scriptTag;
			},
			$html,
		);

		// Minify CSS inside style tags
		$html = preg_replace_callback(
			'/<style[^>]*>(.*?)<\/style>/si',
			function ($matches) {
				$styleTag = $matches[0];
				$cssContent = $matches[1];

				// Only minify if it has content
				if (trim($cssContent)) {
					$minifiedCss = self::minifyCSS($cssContent);
					return str_replace($cssContent, $minifiedCss, $styleTag);
				}

				return $styleTag;
			},
			$html,
		);

		return $html;
	}

	/**
	 * Extreme HTML minification
	 *
	 * @param string $html HTML content
	 * @return string Minified HTML
	 */
	private static function extremeMinify(string $html): string
	{
		$html = self::aggressiveMinify($html);

		// Advanced JavaScript minification for extreme level
		$html = preg_replace_callback(
			'/<script[^>]*>(.*?)<\/script>/si',
			function ($matches) {
				$scriptTag = $matches[0];
				$jsContent = $matches[1];

				// Only minify if it's not an external script (has content)
				if (trim($jsContent)) {
					$minifiedJs = self::extremeMinifyJavaScript($jsContent);
					return str_replace($jsContent, $minifiedJs, $scriptTag);
				}

				return $scriptTag;
			},
			$html,
		);

		// Advanced CSS minification for extreme level
		$html = preg_replace_callback(
			'/<style[^>]*>(.*?)<\/style>/si',
			function ($matches) {
				$styleTag = $matches[0];
				$cssContent = $matches[1];

				// Only minify if it has content
				if (trim($cssContent)) {
					$minifiedCss = self::extremeMinifyCSS($cssContent);
					return str_replace($cssContent, $minifiedCss, $styleTag);
				}

				return $styleTag;
			},
			$html,
		);

		// Remove all newlines and extra spaces
		$html = str_replace(["\r\n", "\r", "\n", "\t"], '', $html);

		// Remove spaces around = in attributes
		$html = preg_replace('/\s*=\s*/', '=', $html);

		// Remove trailing spaces before >
		$html = preg_replace('/\s+>/', '>', $html);

		// Remove spaces after <
		$html = preg_replace('/<\s+/', '<', $html);

		return $html;
	}

	/**
	 * Minify JavaScript code
	 *
	 * @param string $js JavaScript content
	 * @return string Minified JavaScript
	 */
	private static function minifyJavaScript(string $js): string
	{
		// Remove single-line comments (but preserve URLs and regex)
		$js = preg_replace('/(?<!:)\/\/(?![^\r\n]*["\']).*$/m', '', $js);

		// Remove multi-line comments (but preserve license blocks and regex)
		$js = preg_replace('/\/\*(?![*!]).*?\*\//s', '', $js);

		// Remove leading and trailing whitespace from lines
		$js = preg_replace('/^\s+|\s+$/m', '', $js);

		// IMPORTANT: Insert semicolons BEFORE removing newlines
		// This way we can use the newline information to determine where ASI should apply
		$js = self::insertSemicolonsWhereNeeded($js);

		// Now remove empty lines and newlines
		$js = preg_replace('/^\s*\n/m', '', $js);
		$js = preg_replace('/\n/', '', $js);

		// Collapse multiple spaces into single space (but preserve strings)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s+)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// This is whitespace, collapse to single space
					return ' ';
				}
			},
			$js,
		);

		// Remove spaces around operators (be careful with strings)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*([=+\-*\/&|<>!]+)\s*)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// This is an operator, remove surrounding spaces
					return $matches[4];
				}
			},
			$js,
		);

		// Remove spaces around semicolons, commas, braces, brackets
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*([;,{}()\[\]:])\s*)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// Remove spaces around punctuation
					return $matches[4];
				}
			},
			$js,
		);

		// Ensure proper spacing after semicolons when followed by keywords/identifiers
		$js = preg_replace('/;(?=[a-zA-Z_$])/', '; ', $js);

		// Remove unnecessary semicolons before closing braces
		$js = preg_replace('/;\s*}/', '}', $js);

		// Trim and remove final newlines
		return trim($js);
	}

	/**
	 * Minify CSS code
	 *
	 * @param string $css CSS content
	 * @return string Minified CSS
	 */
	private static function minifyCSS(string $css): string
	{
		// Remove CSS comments
		$css = preg_replace('/\/\*.*?\*\//s', '', $css);

		// Remove leading and trailing whitespace from lines
		$css = preg_replace('/^\s+|\s+$/m', '', $css);

		// Remove empty lines
		$css = preg_replace('/^\s*\n/m', '', $css);

		// Collapse multiple whitespace into single space
		$css = preg_replace('/\s+/', ' ', $css);

		// Remove spaces around selectors, braces, and declarations
		$css = preg_replace('/\s*{\s*/', '{', $css);
		$css = preg_replace('/\s*}\s*/', '}', $css);
		$css = preg_replace('/\s*;\s*/', ';', $css);
		$css = preg_replace('/\s*:\s*/', ':', $css);
		$css = preg_replace('/\s*,\s*/', ',', $css);

		// Remove last semicolon before closing brace
		$css = preg_replace('/;(?=\s*})/', '', $css);

		// Remove unnecessary quotes around font names and URLs (when safe)
		$css = preg_replace('/(["\'])([a-zA-Z0-9-_]+)\1/', '$2', $css);

		// Convert zero values (0px, 0em, etc.) to just 0
		$css = preg_replace(
			'/\b0+(px|em|rem|%|pt|pc|in|cm|mm|ex|ch|vw|vh|vmin|vmax)\b/',
			'0',
			$css,
		);

		// Remove leading zeros from decimal values
		$css = preg_replace('/\b0+(\.\d+)/', '$1', $css);

		// Convert RGB values to shorter hex when possible
		$css = preg_replace_callback(
			'/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/',
			function ($matches) {
				$r = sprintf('%02x', $matches[1]);
				$g = sprintf('%02x', $matches[2]);
				$b = sprintf('%02x', $matches[3]);

				// Convert to short hex if possible
				if ($r[0] === $r[1] && $g[0] === $g[1] && $b[0] === $b[1]) {
					return '#' . $r[0] . $g[0] . $b[0];
				}

				return '#' . $r . $g . $b;
			},
			$css,
		);

		return trim($css);
	}

	/**
	 * Apply gzip compression
	 *
	 * @param string $content Content to compress
	 * @return string Compressed content
	 */
	private static function gzipCompress(
		string $content,
		?string $contentType,
	): string {
		if (self::supportsGzip() && self::$useGzip) {
			$compressed = gzencode($content, 9); // Maximum compression level

			// Set appropriate headers for gzip compression
			if (!headers_sent()) {
				header('Content-Encoding: gzip');
				header('Vary: Accept-Encoding');
				header('Content-Length: ' . strlen($compressed));

				if ($contentType !== null) {
					header("Content-Type: $contentType; charset=UTF-8");
				}
			}

			return $compressed;
		}

		if (!headers_sent()) {
			header('Content-Length: ' . strlen($content));

			if ($contentType !== null) {
				header("Content-Type: $contentType; charset=UTF-8");
			}
		}
		return $content;
	}

	/**
	 * Check if client supports gzip compression
	 *
	 * @return bool
	 */
	public static function supportsGzip(): bool
	{
		return function_exists('gzencode') &&
			isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
			strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
	}

	/**
	 * Compress JSON response
	 *
	 * @param array $data Data to JSON encode and compress
	 * @return string Compressed JSON
	 */
	public static function compressJson(array $data): string
	{
		$json = json_encode($data);
		return self::gzipCompress($json, 'application/json');
	}

	/**
	 * Compress component content for SPA responses
	 *
	 * @param string $content Component HTML content
	 * @return string Base64 encoded compressed content
	 */
	public static function compressComponent(string $content): string
	{
		// Apply minification based on compression level
		$minified = self::minify($content, self::$compressionLevel);
		return base64_encode($minified);
	}

	/**
	 * Advanced JavaScript minification for extreme level
	 *
	 * @param string $js JavaScript content
	 * @return string Minified JavaScript
	 */
	private static function extremeMinifyJavaScript(string $js): string
	{
		// At this point, the JS has already been minified by aggressiveMinify()
		// which called minifyJavaScript() and inserted semicolons correctly.
		// We just need to apply additional extreme-level optimizations without 
		// breaking the semicolon logic.

		// Remove all unnecessary spaces around operators (but preserve string literals and space after semicolons)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*([+\-*\/=<>!&|%,:?])\s*)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// This is an operator, remove surrounding spaces
					return $matches[4];
				}
			},
			$js,
		);

		// Remove spaces around punctuation (but preserve string literals)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*([()[\]{}.])\s*)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// Remove spaces around punctuation
					return $matches[4];
				}
			},
			$js,
		);

		// Handle semicolons separately to preserve necessary spacing
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*;\s*)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// Remove spaces around semicolon
					return ';';
				}
			},
			$js,
		);

		// Add back necessary spaces after semicolons when followed by keywords/identifiers
		$js = preg_replace('/;(?=[a-zA-Z_$])/', '; ', $js);

		// Remove extra spaces (multiple spaces to single space) but preserve string literals
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s+)/',
			function ($matches) {
				if (isset($matches[1])) {
					// This is a string literal, don't modify
					return $matches[0];
				} else {
					// Collapse multiple spaces to single space
					return ' ';
				}
			},
			$js,
		);

		// Remove leading/trailing whitespace
		$js = trim($js);

		return $js;
	}

	/**
	 * Insert semicolons at risky boundaries where newline-based ASI would have applied.
	 *
	 * Examples handled:
	 *   - ")" or "]" followed immediately by an identifier (e.g., ")btn" ➜ ");btn")
	 *   - identifier/number followed immediately by a statement-starting keyword (e.g., "x=1const" ➜ "x=1;const")
	 *
	 * We deliberately avoid inserting before else/catch/finally to not break if/try chains.
	 */
	private static function insertSemicolonsWhereNeeded(string $js): string
	{
		// First, let's protect string literals and template literals by temporarily replacing them
		$stringPlaceholders = [];
		$stringIndex = 0;
		
		// Extract and protect string literals (including template literals)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|`(?:[^`\\\\]|\\\\.)*`/',
			function ($matches) use (&$stringPlaceholders, &$stringIndex) {
				$placeholder = '___STRING_PLACEHOLDER_' . $stringIndex . '___';
				$stringPlaceholders[$placeholder] = $matches[0];
				$stringIndex++;
				return $placeholder;
			},
			$js
		);

		// JavaScript ASI rules: Insert semicolons where newlines would trigger ASI
		// Split into lines and process line by line
		$lines = explode("\n", $js);
		$processedLines = [];
		
		for ($i = 0; $i < count($lines); $i++) {
			$currentLine = trim($lines[$i]);
			$nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
			
			// Skip empty lines
			if (empty($currentLine)) {
				continue;
			}
			
			// Check if current line needs a semicolon based on next line
			$needsSemicolon = false;
			
			if (!empty($nextLine)) {
				// Check if current line could end a statement (after trimming)
				// Look for: identifiers, numbers, ), ], }, `, etc.
				$currentEndsWithStatement = preg_match('/[a-zA-Z0-9_$\)\]\}`"]\\s*$/', $currentLine);
				
				// Check if next line starts with something that begins a new statement
				$nextStartsWithStatement = preg_match('/^(const|let|var|function|class|async|import|export|return|throw|if|for|while|do|try|switch|case|default|break|continue|yield|new)\b/', $nextLine);
				
				// Check for IIFE patterns
				$nextStartsWithIIFE = preg_match('/^\((?:function|async\s+function|\()/', $nextLine);
				
				// Check if next line starts with an identifier (potential method call or variable reference)
				$nextStartsWithIdentifier = preg_match('/^[a-zA-Z_$]/', $nextLine) && !$nextStartsWithStatement;
				
				// Don't insert semicolon before else, catch, finally, while (in do-while)
				$nextIsControlContinuation = preg_match('/^(else|catch|finally)\b/', $nextLine);
				$nextIsDoWhile = preg_match('/^while\s*\(/', $nextLine) && preg_match('/\}\s*$/', $currentLine);
				
				if ($currentEndsWithStatement && !$nextIsControlContinuation && !$nextIsDoWhile) {
					if ($nextStartsWithStatement || $nextStartsWithIIFE || $nextStartsWithIdentifier) {
						// Special case: don't insert semicolon between constructor and opening paren
						// e.g., "new IntersectionObserver" followed by "(function..." should NOT get semicolon
						$isConstructorCall = preg_match('/\bnew\s+[a-zA-Z_$][a-zA-Z0-9_$]*\s*$/', $currentLine) && 
						                     preg_match('/^\(/', $nextLine);
						
						if (!$isConstructorCall) {
							$needsSemicolon = true;
						}
					}
				}
			}
			
			// Add semicolon if needed and line doesn't already end with semicolon or brace
			if ($needsSemicolon && !preg_match('/[;}]\s*$/', $currentLine)) {
				$currentLine .= ';';
			}
			
			$processedLines[] = $currentLine;
		}
		
		// Join lines back together
		$js = implode('', $processedLines);
		
		// Additional specific fixes for edge cases that might have been created
		$js = str_replace('forEach;', 'forEach', $js);
		$js = str_replace('map;', 'map', $js);
		$js = str_replace('filter;', 'filter', $js);
		$js = str_replace('reduce;', 'reduce', $js);
		$js = str_replace('addEventListener;', 'addEventListener', $js);
		$js = str_replace('querySelector;', 'querySelector', $js);
		$js = str_replace('getElementById;', 'getElementById', $js);
		
		// Fix constructor calls that might have gotten semicolons
		$js = preg_replace('/\bnew\s+([a-zA-Z_$][a-zA-Z0-9_$]*);(\()/', 'new $1$2', $js);

		// Restore string literals
		foreach ($stringPlaceholders as $placeholder => $original) {
			$js = str_replace($placeholder, $original, $js);
		}

		return $js;
	}

	/**
	 * Advanced CSS minification for extreme level
	 *
	 * @param string $css CSS content
	 * @return string Minified CSS
	 */
	private static function extremeMinifyCSS(string $css): string
	{
		// Start with aggressive minification
		$css = self::minifyCSS($css);

		// Remove all unnecessary spaces around operators and symbols
		$css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);

		// Remove spaces around parentheses
		$css = preg_replace('/\s*\(\s*/', '(', $css);
		$css = preg_replace('/\s*\)\s*/', ')', $css);

		// Remove any remaining multiple spaces
		$css = preg_replace('/\s+/', ' ', $css);

		// Remove leading/trailing whitespace
		$css = trim($css);

		return $css;
	}

	/**
	 * Auto-detect best compression level based on content size
	 *
	 * @param string $content Content to analyze
	 * @return int Recommended compression level
	 */
	private static function detectOptimalLevel(string $content): int
	{
		$size = strlen($content);

		if ($size < 1024) {
			// Less than 1KB
			return Compressor::LEVEL_BASIC;
		} elseif ($size < 10240) {
			// Less than 10KB
			return Compressor::LEVEL_AGGRESSIVE;
		} else {
			// 10KB or more
			return Compressor::LEVEL_EXTREME;
		}
	}
}
