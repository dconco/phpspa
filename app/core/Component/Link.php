<?php

namespace phpSPA\Component;

function Link ($to, $id, $class, $style, $children): string
{
   return <<<HTML
      <a href="{$to}" id="{$id}" class="{$class}" style="{$style}" data-type="phpspa-link-tag">{$children}</a>
   HTML;
}