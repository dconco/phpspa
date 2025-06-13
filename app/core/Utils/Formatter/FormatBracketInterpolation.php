<?php

namespace phpSPA\Core\Utils\Formatter;

abstract class FormatBracketInterpolation
{
   protected function format (&$content): void
   {
      // Replace bracket interpolation {{! ... !}}
      $content = preg_replace(
       '/\{\{!\s*.*?\s*!\}\}/s',
       '',
       $content,
      );

      // Replace bracket interpolation {{ ... }}
      $content = preg_replace_callback(
       '/\{\{\s*(.*?)\s*\}\}/s',
       function ($matches)
       {
          $val = trim($matches[1], ';');
          return '<' . '?php print_r(' . $val . '); ?' . '>';
       },
       $content,
      );
   }
}