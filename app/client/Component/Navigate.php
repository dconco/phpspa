<?php

namespace phpSPA\Component;

use phpSPA\Core\Helper\Enums\NavigateState;

/**
 * @param string $path
 * @param string|NavigateState $state
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md
 * @return string
 */
function Navigate (string $path, string|NavigateState $state = NavigateState::PUSH): string
{
   if (!$state instanceof NavigateState)
      $state = NavigateState::from($state);
   $state = $state->value;

   return <<<HTML
      <script data-type="phpspa/script">
         phpspa.navigate("$path", "$state");
      </script>
   HTML;
}