<?php

namespace phpSPA\Core\Utils\Formatter;

use phpSPA\Exceptions\AppException;

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
        $pattern = '/<([A-Z][a-zA-Z0-9.:]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

        $updatedDom = preg_replace_callback(
            $pattern,
            function ($matches) {
                $matches = array_map('trim', $matches);

                $component = $matches[1];
                $className = '';
                $methodName = '';

                // Handle namespace.class::method syntax
                if (strpos($component, '::') !== false) {
                    list($className, $methodName) = explode('::', $component);
                    if (strpos($className, '.')) {
                        $className = str_replace('.', '\\', $className);
                    }
                } else {
                    // Handle namespace.class syntax or simple class name
                    if (strpos($component, '.')) {
                        $className = str_replace('.', '\\', $component);
                    } else {
                        $className = $component;
                    }
                }

                // Parse attributes
                $attributes = self::parseAttributesToArray($matches[2]);

                if (isset($matches[3])) {
                    // Recursively process children FIRST and capture the result
                    $processedChildren = self::format($matches[3]);

                    // Now assign the processed children
                    $attributes['children'] = $processedChildren;
                }

                // Handle different component types
                if ($methodName) {
                    // Class::method syntax
                    if (!class_exists($className)) {
                        throw new AppException("Class {$className} does not exist.");
                    }

                    if (method_exists($className, $methodName)) {
                        $reflection = new \ReflectionMethod($className, $methodName);
                        if ($reflection->isStatic()) {
                            return $className::$methodName(...$attributes);
                        } else {
                            return (new $className)->$methodName(...$attributes);
                        }
                    } else {
                        throw new AppException("Method {$methodName} does not exist in class {$className}.");
                    }
                } elseif (class_exists($className)) {
                    // Class syntax
                    if (method_exists($className, '__render')) {
                        return (new $className)->__render(...$attributes);
                    } else {
                        throw new AppException("Class {$className} does not have __render method.");
                    }
                } elseif (function_exists($className)) {
                    // Function syntax
                    return call_user_func_array($className, $attributes);
                } else {
                    throw new AppException("Component {$component} does not exist.");
                }
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
