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

		// Collapse multiple whitespace characters into single space
		$html = preg_replace('/\s+/', ' ', $html);

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

		// Remove empty lines
		$js = preg_replace('/^\s*\n/m', '', $js);

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

		// Remove unnecessary semicolons before closing braces
		$js = preg_replace('/;\s*}/', '}', $js);

		// Insert semicolons where line joining could break code
		$js = self::insertSemicolonsWhereNeeded($js);

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
		// Start with aggressive minification
		$js = self::minifyJavaScript($js);

		// Remove all unnecessary spaces around operators (but preserve string literals)
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1|(\s*([+\-*\/=<>!&|%,;:?])\s*)/',
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

		// Ensure semicolons exist where statements abut
		$js = self::insertSemicolonsWhereNeeded($js);

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
		// First, let's protect string literals by temporarily replacing them
		$stringPlaceholders = [];
		$stringIndex = 0;
		
		// Extract and protect string literals
		$js = preg_replace_callback(
			'/(["\'])(?:(?=(\\\\?))\2.)*?\1/',
			function ($matches) use (&$stringPlaceholders, &$stringIndex) {
				$placeholder = '___STRING_PLACEHOLDER_' . $stringIndex . '___';
				$stringPlaceholders[$placeholder] = $matches[0];
				$stringIndex++;
				return $placeholder;
			},
			$js
		);

		// 1) After closing paren/brace/bracket before an identifier start
		//    - common case: ")btn" ➜ ");btn"
		//    But avoid breaking method calls like "forEach(function"
		$js = preg_replace('/\)(?=[$_A-Za-z])/', ');', $js);
		$js = preg_replace('/\](?=[$_A-Za-z])/', '];', $js);

		// Avoid breaking "}else", "}catch", "}finally" by not inserting before those keywords
		// Only insert when next token is an identifier that is NOT else/catch/finally/while
		$js = preg_replace(
			'/\}(?=\s*(?!else\b)(?!catch\b)(?!finally\b)(?!while\b)[$_A-Za-z])/',
			'};',
			$js,
		);

		// 2) Before statement-starting keywords when previous token ends with ident/number/]/)/}
		//    (allow a single whitespace between previous token and keyword)
		//    Exclude 'while' to preserve do{...}while() structure.
		$stmtKeywords =
			'(?:const|let|var|function|class|async|await|import|export|return|throw|switch|for|do|try|if|new|yield)';
		$js = preg_replace(
			'/([$_A-Za-z0-9\)\]\}`])\s*(?=' . $stmtKeywords . '\b)/',
			'$1;',
			$js,
		);

		// 3) Before IIFE starts: if previous token ends with ident/number/]/)/}, and next is (function|((...)) or (async
		//    This avoids accidental calls due to line-joining: `a=1\n(function(){})()` => `a=1;(function(){})()`
		$js = preg_replace(
			'/([$_A-Za-z0-9\)\]\}`])\s*(?=\((?:function|async|\())/',
			'$1;',
			$js,
		);

		// Join back pairs we must not split
		$js = str_replace('async;function', 'async function', $js);
		$js = str_replace('else;if', 'else if', $js);

		// Fix specific broken patterns that should not have semicolons
		$js = str_replace('forEach;(', 'forEach(', $js);
		$js = str_replace('map;(', 'map(', $js);
		$js = str_replace('filter;(', 'filter(', $js);
		$js = str_replace('reduce;(', 'reduce(', $js);
		$js = str_replace('addEventListener;(', 'addEventListener(', $js);
		$js = str_replace('querySelector;(', 'querySelector(', $js);
		$js = str_replace('getElementById;(', 'getElementById(', $js);

		// Fix broken variable names where keywords were detected inside identifiers
		$js = str_replace('en;try', 'entry', $js);
		$js = str_replace('ent;ry', 'entry', $js);  // in case the pattern is different
		$js = str_replace('e;try', 'entry', $js);   // other variations

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
