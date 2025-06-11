<?php

function HashComp ($children)
{
   return <<<HTML
      <div id=hashID>
         <p>{$children}</p>
      </div>
   HTML;
}