<?php

namespace phpSPA\Core\Helper;

trait ComponentParser
{
   protected static string|array $attributes;

   private static function parseAttributesToArray (): void
   {
      $attributes = [];
      $pattern = '/([a-zA-Z_-]+)\s*=\s*(?|"([^"]*)"|\'([^\']*)\')/';

      // Remove newlines and excessive spaces for easier parsing
      $normalized = preg_replace('/\s+/', ' ', trim(self::$attributes));

      if (preg_match_all($pattern, $normalized, $matches, PREG_SET_ORDER))
      {
         foreach ($matches as $match)
         {
            $attributes[$match[1]] = $match[2];
         }
      }

      self::$attributes = $attributes;
   }
}