<?php

namespace phpSPA\Component;

/**
 * @param string $path
 * @param string $state
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md
 * @return string
 */
function Navigate (string $path, string $state = 'push'): string
{
   return <<<HTML
      <script data-type="phpspa/script">
         phpspa.navigate("$path", "$state");
         console.log("$path", "$state")
      </script>
   HTML;
}