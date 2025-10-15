<?php

namespace Component;

use PhpSPA\Core\Helper\StateManager;

/**
 * Creates a new StateManager instance for reactive component state.
 *
 * @author dconco <concodave@gmail.com>
 * @param string $stateKey The unique key identifying the state variable.
 * @param mixed  $default  The default value to initialize the state with.
 * @return StateManager    The state manager instance.
 * @see https://phpspa.readthedocs.io/en/stable/hooks/use-state
 */
function useState (string $stateKey, $default): StateManager
{
    return new StateManager($stateKey, $default);
}
