<?php

namespace phpSPA\Component;

function Link ($to, $children, ...$HtmlAttr): string
{
   $attr = HTMLAttrInArrayToString($HtmlAttr);

   return <<<HTML
      <a href="{$to}" data-type="phpspa-link-tag" $attr>{$children}</a>
   HTML;
}