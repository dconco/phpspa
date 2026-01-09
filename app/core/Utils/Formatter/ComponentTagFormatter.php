<?php

namespace PhpSPA\Core\Utils\Formatter;

use PhpSPA\Exceptions\AppException;
use PhpSPA\Core\Helper\ComponentScope;
use ReflectionMethod;

/**
 * Component tag formatting utilities
 *
 * This trait provides methods for parsing and formatting custom component tags
 * within HTML markup. It handles the transformation of custom component syntax
 * into executable PHP components within the PhpSPA framework.
 *
 * @package PhpSPA\Core\Utils\Formatter
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
trait ComponentTagFormatter
{
   use \PhpSPA\Core\Helper\ComponentParser;

   /**
    * Formats the given DOM structure.
    *
    * @param mixed $dom Reference to the DOM object or structure to be formatted.
    * @return string
    */
   protected static function format(string $dom): string
   {
      $pattern = '/<([@\$]?[A-Z][a-zA-Z0-9.:]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

      $updatedDom = preg_replace_callback(
         $pattern,
         function ($matches) {
            $matches = array_map('trim', $matches);

            $component = $matches[1];
            $className = '';
            $methodName = '';
            $isVariable = false;

            // Check if it's a variable component
            if (str_starts_with($component, '$') || str_starts_with($component, '@')) {
               $isVariable = true;
               $variableName = substr($component, 1);
               
               // Check if variable exists in component scope first
               if (ComponentScope::has($variableName)) {
                  $callable = ComponentScope::get($variableName);
               } elseif (isset($GLOBALS[$variableName])) {
                  // Fallback to global scope
                  $callable = $GLOBALS[$variableName];
               } else {
                  throw new AppException("Variable \${$variableName} does not exist.");
               }
               
               if (!is_callable($callable)) {
                  throw new AppException("Variable \${$variableName} is not callable.");
               }
            } else {
               // Handle namespace.class::method syntax
               if (strpos($component, '::') !== false) {
                  [$className, $methodName] = explode('::', $component);
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
            }

            // Parse attributes
            $attributes = self::parseAttributesToArray($matches[2]);

            if (isset($matches[3])) {
               // Recursively process children FIRST and capture the result
               $processedChildren = self::format($matches[3]);

               // Now assign the processed children
               $decoded = base64_decode($processedChildren ?? '', true);

               if ($decoded !== false) {
                  $unserialized = @unserialize($decoded);
                  if ($unserialized !== false || $decoded === 'b:0;')
                     $attributes['children'] = $unserialized;
                  else
                     $attributes['children'] = $processedChildren;
               } else {
                  $attributes['children'] = $processedChildren;
               }
            }

            // Handle different component types
            if ($isVariable) {
               // Variable component
               return \call_user_func_array($callable, $attributes);
            } elseif ($methodName) {
               // Class::method syntax
               if (!class_exists($className)) {
                  throw new AppException("Class {$className} does not exist.");
               }

               if (method_exists($className, $methodName)) {
                  $reflection = new ReflectionMethod($className, $methodName);

                  if ($reflection->isStatic()) {
                     return \call_user_func_array([$className, $methodName], $attributes);
                  } else {
                     return (new $className)->$methodName(...$attributes);
                  }
               } else {
                  throw new AppException("Method {$methodName} does not exist in class {$className}.");
               }
            } elseif (class_exists($className)) {
               // Class syntax
               if (method_exists($className, '__render')) {
                  $reflection = new ReflectionMethod($className, '__render');

                  if ($reflection->isStatic()) {
                     return \call_user_func_array([$className, '__render'], $attributes);
                  } else {
                     return (new $className)->__render(...$attributes);
                  }
               } else {
                  throw new AppException("Class {$className} does not have __render method.");
               }
            } elseif (function_exists($className)) {
               // Function syntax
               return \call_user_func_array($className, $attributes);
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

      return $updatedDom ?? '';
   }
}
