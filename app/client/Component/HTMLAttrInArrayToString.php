<?php

namespace phpSPA\Component;

/**
 * @param array $HtmlAttr
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/5-html-attr-in-array-to-string-function
 * @package phpSPA\Component
 * @since 1.1.0
 * @return string
 */
function HTMLAttrInArrayToString (array $HtmlAttr): string
{
   $attr = "";
   foreach ($HtmlAttr as $AttrName => $AttrValue)
   {
      $attr .= "$AttrName=\"$AttrValue\" ";
   }
   return $attr;
}