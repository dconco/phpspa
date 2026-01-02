<?php

namespace PhpSPA\Core\Helper;
use ReflectionFunction;

/**
 * Class CallableInspector
 *
 * Provides utilities for inspecting and analyzing PHP callables.
 * Useful for determining callable types, extracting reflection information,
 * and facilitating dynamic invocation or introspection of functions, methods, or closures.
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @static
 */
class CallableInspector
{
   /**
    * Checks if the given callable has a parameter with the specified name.
    *
    * @param callable $func The callable to inspect.
    * @param string $paramName The name of the parameter to look for.
    * @return bool Returns true if the parameter exists, false otherwise.
    */
   public static function hasParam (callable $func, string $paramName): bool
   {
      $ref = new ReflectionFunction($func);

      foreach ($ref->getParameters() as $param)
      {
         if ($param->getName() === $paramName)
         {
            return true;
         }
      }
      return false;
   }

   /**
    * Retrieves the value of a specified property from a given class or object.
    *
    * @param object|string $class    The object instance or class name from which to retrieve the property.
    * @param string        $property The name of the property to retrieve.
    * @return mixed                  The value of the specified property, or null if not found.
    */
   public static function getProperty (object|string $classOrObject, string $property): mixed
   {
      $reflection = new \ReflectionClass($classOrObject);

      if (!$reflection->hasProperty($property))
      {
         return null;
      }

      $prop = $reflection->getProperty($property);
      $prop->setAccessible(true);

      try
      {
         // For static properties or when passing a class name
         if (is_string($classOrObject) && $prop->isStatic())
         {
            $value = $prop->getValue();
         }
         // For instance properties
         else if (is_object($classOrObject))
         {
            if (!$prop->isInitialized($classOrObject))
            {
               return null;
            }
            $value = $prop->getValue($classOrObject);
         }
         // Invalid case - instance property accessed with class name
         else
         {
            throw new \LogicException("Cannot access non-static property '$property' without an object instance");
         }

         return $value;
      }
      finally
      {
         $prop->setAccessible(false); // Ensure accessibility is always reset
      }
   }
}