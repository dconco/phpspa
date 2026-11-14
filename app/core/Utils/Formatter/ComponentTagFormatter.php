<?php

namespace PhpSPA\Core\Utils\Formatter;

use PhpSPA\Exceptions\AppException;
use PhpSPA\Core\Helper\ComponentValueCodec;
use PhpSPA\Core\Helper\ComponentScope;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

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

            // Parse attributes. Most component tags have none, so skip the
            // two regex passes inside parseAttributesToArray for empty text.
            $attributes = ($matches[2] ?? '') === '' ? [] : self::parseAttributesToArray($matches[2]);

            if (isset($matches[3])) {
               // Recursively process children FIRST and capture the result
               $processedChildren = self::format($matches[3]);

               // Now assign the processed children
               $decoded = ComponentValueCodec::decode($processedChildren ?? '', $valid);
               $attributes['children'] = $valid ? $decoded : $processedChildren;
            }

            // Handle different component types
            if ($isVariable) {
               $callable ??= null;

               // Variable component
               $reflection = new ReflectionFunction($callable);
               $fileName = $reflection->getFileName();
               try {
                  self::assertSafeComponentCallable($reflection, (string) $component);
                  return self::requireStringComponentResult(
                     \call_user_func_array($callable, $attributes),
                     (string) $component,
                  );
               } catch (\Throwable $e) {
                  throw new \InvalidArgumentException("{$e->getMessage()} at component <$callable /> in {$fileName} on line {$e->getLine()}", 1);
               }
            } elseif ($methodName) {
               // Class::method syntax
               if (!class_exists($className)) {
                  throw new \BadFunctionCallException("Class {$className} does not exist.");
               }

               if (method_exists($className, $methodName)) {
                  $reflection = ReflectionMethod::createFromMethodName("$className::$methodName");

                  try {
                     self::assertSafeComponentCallable($reflection, "$className::$methodName");

                     if ($reflection->isStatic()) {
                        $result = \call_user_func_array([$className, $methodName], $attributes);
                     } else {
                        $result = (new $className)->$methodName(...$attributes);
                     }

                     return self::requireStringComponentResult($result, "$className::$methodName");
                  } catch (\Throwable $e) {
                     throw new \InvalidArgumentException("{$e->getMessage()} at component <$className::$methodName /> in {$reflection->getFileName()} on line {$e->getLine()}", 1);
                  }
               } else {
                  throw new \BadFunctionCallException("Method {$methodName} does not exist in class {$className}.");
               }
            } elseif (class_exists($className)) {
               // Class syntax
               if (method_exists($className, '__render')) {
                  $reflection = ReflectionMethod::createFromMethodName("$className::__render");

                  try {
                     self::assertSafeComponentCallable($reflection, "$className::__render");

                     if ($reflection->isStatic()) {
                        $result = \call_user_func_array([$className, '__render'], $attributes);
                     } else {
                        $result = (new $className)->__render(...$attributes);
                     }

                     return self::requireStringComponentResult($result, "$className::__render");
                  } catch (\Throwable $e) {
                     throw new \InvalidArgumentException("{$e->getMessage()} at component <$className /> in {$reflection->getFileName()} on line {$e->getLine()}", 1);
                  }
               } else {
                  throw new \BadFunctionCallException("Class {$className} does not have __render method.");
               }
            } elseif (function_exists($className)) {
               // Function syntax
               $reflection = new ReflectionFunction($className);
               $fileName = $reflection->getFileName();

               try {
                  self::assertSafeComponentCallable($reflection, $className);
                  return self::requireStringComponentResult(
                     \call_user_func_array($className, $attributes),
                     $className,
                  );
               } catch (\Throwable $e) {
                  throw new \InvalidArgumentException("{$e->getMessage()} at component <$className /> in {$fileName} on line {$e->getLine()}", 1);
               }
            } else {
               throw new \BadFunctionCallException("Component {$component} does not exist.");
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

   /**
    * Component tags are a rendering DSL, not a general PHP invocation syntax.
    * Never expose internal PHP functions/classes, and reject callables whose
    * declared return type cannot be rendered as HTML text.
    */
   private static function assertSafeComponentCallable(
      ReflectionFunctionAbstract $reflection,
      string $displayName,
   ): void {
      if ($reflection->isInternal()) {
         throw new \BadFunctionCallException("Internal PHP callable {$displayName} cannot be used as a component.");
      }

      if ($reflection instanceof ReflectionMethod && $reflection->getDeclaringClass()->isInternal()) {
         throw new \BadFunctionCallException("Internal PHP class methods cannot be used as components.");
      }

      $returnType = $reflection->getReturnType();
      if ($returnType === null) return;

      $types = $returnType instanceof ReflectionUnionType
         ? $returnType->getTypes()
         : [$returnType];

      foreach ($types as $type) {
         if (!$type instanceof ReflectionNamedType || !in_array($type->getName(), ['string', 'mixed'], true)) {
            throw new \BadFunctionCallException("Component {$displayName} must return a string.");
         }
      }
   }

   private static function requireStringComponentResult(mixed $result, string $displayName): string
   {
      if (!is_string($result)) {
         throw new \UnexpectedValueException("Component {$displayName} returned a non-string value.");
      }

      return $result;
   }
}
