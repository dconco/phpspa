<?php

namespace phpSPA\Core\Utils\Formatter;

use phpSPA\Exceptions\AppException;
use phpSPA\Core\Helper\CallableInspector;

/**
 * Component tag formatting utilities
 *
 * This trait provides methods for parsing and formatting custom component tags
 * within HTML markup. It handles the transformation of custom component syntax
 * into executable PHP components within the phpSPA framework.
 *
 * @package phpSPA\Core\Utils\Formatter
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @uses \phpSPA\Core\Helper\ComponentParser
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
    protected static function format(string $dom): string
    {
        $pattern = '/<([A-Z][a-zA-Z0-9.]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

        $updatedDom = preg_replace_callback(
            $pattern,
            function ($matches) {
                $matches = array_map('trim', $matches);

                if (strpos($matches[1], '.')) {
                    $matches[1] = str_replace('.', '\\', $matches[1]);
                }

                if (!function_exists($matches[1]) && !method_exists($matches[1], '__render')) {
                    throw new AppException(
                        "Component Function {$matches[1]} does not exist.",
                    );
                }

                // Parse attributes
                $attributes = self::parseAttributesToArray($matches[2]);

                if (isset($matches[3])) {
                    // Recursively process children FIRST and capture the result
                    $processedChildren = self::format($matches[3]);

                    // Now assign the processed children
                    $attributes['children'] = $processedChildren;
                }

                // Validate parameters
                foreach (array_keys($attributes) as $attrKey) {
                    if (!CallableInspector::hasParam($matches[1], $attrKey)) {
                        // throw new AppException("Component Function {$matches[1]} does not accept property '{$attrKey}'.");
                    }
                }

                return class_exists($matches[1])
                    ? (new $matches[1])->__render(...$attributes)
                    : call_user_func_array($matches[1], $attributes);
            },
            $dom,
        );

        // If the DOM changed, run again recursively
        if ($updatedDom !== $dom) {
            return self::format($updatedDom) ?? '';
        }

        return $updatedDom;
    }
}
