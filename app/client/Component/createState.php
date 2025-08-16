<?php

namespace Component;

use phpSPA\Core\Helper\StateManager;

/**
 * Creates and returns a new StateManager instance for managing component state.
 *
 * Initializes state with the specified key and default value, allowing components
 * to persist and reactively update their state.
 *
 * @param string $stateKey   The unique key identifying the state variable.
 * @param mixed  $default    The default value to initialize the state with.
 * @see https://phpspa.readthedocs.io/en/latest/17-state-management.md
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @package phpSPA\Component
 * @return StateManager      The state manager instance for the specified key.
 */
function createState(string $stateKey, $default): StateManager
{
	return new StateManager($stateKey, $default);
}
