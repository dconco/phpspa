<?php

namespace phpSPA\Component;

/**
 * @param string $to
 * @param string $children
 * @param string ...$HtmlAttr
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component
 * @since v1.1.0
 * @package phpSPA\Component
 * @return string
 */
function Link (string $to, string $children, string ...$HtmlAttr): string
{
   $attr = HTMLAttrInArrayToString($HtmlAttr);

   return <<<HTML
      <a href="{$to}" data-type="phpspa-link-tag" $attr>{$children}</a>
   HTML;
}