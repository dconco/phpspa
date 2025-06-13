<?php

namespace phpSPA\Component;

use phpSPA\Core\Helper\StateManagement;

/**
 * Summary of phpSPA\Component\createState
 * 
 * @param string $stateKey
 * @param mixed $default
 * @see https://phpspa.readthedocs.io/en/latest/17-state-management.md
 * @return StateManagement
 */
function createState (string $stateKey, $default): StateManagement
{
   if (session_status() < 2) session_start();
   return new StateManagement($stateKey, $default);
}