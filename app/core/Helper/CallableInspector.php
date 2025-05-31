<?php

namespace phpSPA\Helper;
use ReflectionFunction;

/**
 * Class CallableInspector
 *
 * Provides utilities for inspecting and analyzing PHP callables.
 * Useful for determining callable types, extracting reflection information,
 * and facilitating dynamic invocation or introspection of functions, methods, or closures.
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
}