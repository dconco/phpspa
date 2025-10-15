<?php

namespace Component;

use PhpSPA\Core\Helper\StateManager;

/**
 * Creates a new StateManager instance for reactive component state.
 *
 * @deprecated Please use the useState function instead.
 * @author dconco <concodave@gmail.com>
 * @param string $stateKey The unique key identifying the state variable.
 * @param mixed  $default  The default value to initialize the state with.
 * @return StateManager    The state manager instance.
 * @see https://phpspa.readthedocs.io/en/stable/hooks/use-state
 */
function createState(string $stateKey, $default): StateManager
{
    return new StateManager($stateKey, $default);
}
