<?php

namespace phpSPA\Component;

use phpSPA\Core\Helper\StateManager;

/**
 * @param string $stateKey
 * @param mixed $default
 * @see https://phpspa.readthedocs.io/en/latest/17-state-management.md
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @package phpSPA\Component
 * @return StateManager
 */
function createState (string $stateKey, $default): StateManager
{
   if (session_status() < 2) {
      if (session_status() == PHP_SESSION_ACTIVE) {
         session_destroy();
      }
      session_start();
   }
   return new StateManager($stateKey, $default);
}