<?php

namespace Component;

use phpSPA\Core\Helper\Enums\NavigateState;

/**
 * Generates a script tag to navigate to a specified path using the phpspa JavaScript framework.
 *
 * This function creates client-side navigation functionality that works seamlessly
 * with the single-page application architecture of phpSPA.
 *
 * @param string $path The target path to navigate to.
 * @param string|NavigateState $state The navigation state, either as a string or a NavigateState enum (default is NavigateState::PUSH).
 * @package phpSPA\Component
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md
 * @since v1.1.0
 * @return string The HTML script tag that triggers client-side navigation.
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
