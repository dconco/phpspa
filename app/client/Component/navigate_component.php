<?php

namespace Component;

use PhpSPA\Core\Helper\Enums\NavigateState;

/**
 * Generates client-side navigation script.
 *
 * @param string $path Target path
 * @param string|NavigateState $state Navigation state (push/replace)
 * @return string Navigation script tag
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/8-navigate-component Navigation Documentation
 * @author dconco <concodave@gmail.com>
 */
function Navigate(
    string $path,
    string|NavigateState $state = NavigateState::PUSH,
): string {
    if (!$state instanceof NavigateState) {
        $state = NavigateState::from($state);
    }
    $state = $state->value;

    return <<<HTML
	   <script data-type="phpspa/script">
	      phpspa.navigate("$path", "$state");
	   </script>
	HTML;
}
