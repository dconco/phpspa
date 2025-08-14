<?php

namespace Component;

use phpSPA\Core\Helper\Enums\NavigateState;

/**
 * Generates a script tag to navigate to a specified path using the phpspa JavaScript framework.
 *
 * @param string $path The target path to navigate to.
 * @param string|NavigateState $state The navigation state, either as a string or a NavigateState enum (default is NavigateState::PUSH).
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md
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
