<?php

namespace phpSPA\Helper;

use phpSPA\Exceptions\AppException;

trait ComponentTagFormatter
{
   static private function parseAttributesToArray ($attributesString)
   {
      $attributes = [];
      $pattern = '/([a-zA-Z_-]+)\s*=\s*(?|"([^"]*)"|\'([^\']*)\')/';

      // Remove newlines and excessive spaces for easier parsing
      $normalized = preg_replace('/\s+/', ' ', trim($attributesString));

      if (preg_match_all($pattern, $normalized, $matches, PREG_SET_ORDER))
      {
         foreach ($matches as $match)
         {
            $attributes[$match[1]] = $match[2];
         }
      }

      return $attributes;
   }

   /**
    * Formats the given DOM structure.
    *
    * @param mixed $dom Reference to the DOM object or structure to be formatted.
    * @return void
    */
   static protected function format (&$dom)
   {
      $pattern = '/<([A-Z][a-zA-Z0-9]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

      $dom = preg_replace_callback($pattern, function ($matches)
      {
         $matches = array_map('trim', $matches);

         if (strpos($matches[1], '.'))
         {
            $matches[1] = str_replace('.', '\\', $matches[1]);
         }

         if (!function_exists($matches[1]))
         {
            throw new AppException("Component Function {$matches[1]} does not exist.");
         }

         $args = self::parseAttributesToArray($matches[2]);
         if (isset($matches[3])) $args['children'] = $matches[3];

         foreach (array_keys($args) as $key)
         {
            if (!CallableInspector::hasParam($matches[1], $key))
            {
               throw new AppException("Component Function {$matches[1]} does not accept property '{$key}'.");
            }
         }

         return call_user_func_array($matches[1], $args);
      }, $dom);
   }
}