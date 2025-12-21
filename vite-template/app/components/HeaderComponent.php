<?php

use function Component\import;

function HeaderComponent($children) {
   $logo = import('public/assets/logo.svg');

   return <<<HTML
      <span class="pill">
         <img src="{$logo}" class="h-4 w-4" alt="PhpSPA + Vite Client Logo" />
         PhpSPA + Vite
      </span>
   HTML;
}
