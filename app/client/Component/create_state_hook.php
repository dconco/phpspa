<?php

namespace Component;

use PhpSPA\Core\Helper\StateManager;

/**
 * Creates a new StateManager instance for reactive component state.
 *
 * @package Component
 * @deprecated Please use the useState function instead.
 * @author dconco <me@dconco.tech>
 * @param string $stateKey The unique key identifying the state variable.
 * @param mixed  $default  The default value to initialize the state with.
 * @return StateManager    The state manager instance.
 * @see https://phpspa.tech/hooks/use-state
 */
function createState(string $stateKey, $default): StateManager
{
    return new StateManager($stateKey, $default);
}
