<?php

function HashComp ($children, $id, $class)
{
   return <<<HTML
      <div id="{$id}" class="{$class}">
         <p>{$children}</p>
      </div>
   HTML;
}