<?php

namespace Component;

use PhpSPA\Core\Helper\Enums\NavigateState;
use PhpSPA\Http\Security\Nonce;

/**
 * Generates client-side navigation script.
 *
 * @package Component
 * @param string $path Target path
 * @param string|NavigateState $state Navigation state (push/replace)
 * @return string Navigation script tag
 * @author dconco <me@dconco.tech>
 * @see https://phpspa.tech/navigations/navigate-component
 */
function Navigate (
    string $path,
    string|NavigateState $state = NavigateState::PUSH,
): string {
    if (!$state instanceof NavigateState) {
        $state = NavigateState::from($state);
    }
    $state = $state->value;
    $nonce = Nonce::attr();

    return <<<HTML
	   <script $nonce>
	      phpspa.navigate("{$path}", "{$state}");
	   </script>
	HTML;
}
