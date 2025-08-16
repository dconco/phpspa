<?php

namespace phpSPA\Core\Utils\Formatter;

use phpSPA\Exceptions\AppException;
use phpSPA\Core\Helper\CallableInspector;

/**
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @use \phpSPA\Core\Helper\ComponentParser
 * @static
 */
trait ComponentTagFormatter
{
	use \phpSPA\Core\Helper\ComponentParser;

	/**
	 * Formats the given DOM structure.
	 *
	 * @param mixed $dom Reference to the DOM object or structure to be formatted.
	 * @return void
	 */
	protected static function format(&$dom)
	{
		$pattern = '/<([A-Z][a-zA-Z0-9.]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

		$updatedDom = preg_replace_callback(
			$pattern,
			function ($matches) {
				$matches = array_map('trim', $matches);

				if (strpos($matches[1], '.')) {
					$matches[1] = str_replace('.', '\\', $matches[1]);
				}

				if (!function_exists($matches[1])) {
					throw new AppException(
						"Component Function {$matches[1]} does not exist.",
					);
				}

				self::$attributes = $matches[2];
				self::parseAttributesToArray();

				if (isset($matches[3])) {
					self::$attributes['children'] = $matches[3];
				}

				// Recursively resolve children before calling the function
				if (isset(self::$attributes['children'])) {
					self::format(self::$attributes['children']);
				}

				foreach (array_keys(self::$attributes) as $attrKey) {
					if (!CallableInspector::hasParam($matches[1], $attrKey)) {
						// throw new AppException("Component Function {$matches[1]} does not accept property '{$attrKey}'.");
					}
				}

				return call_user_func_array($matches[1], self::$attributes);
			},
			$dom,
		);

		// If the DOM changed, run again recursively
		if ($updatedDom !== $dom) {
			$dom = $updatedDom;
			self::format($dom);
		} else {
			$dom = $updatedDom; // final update
		}
	}
}
