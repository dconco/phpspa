<?php

namespace phpSPA\Component;

use phpSPA\Core\Helper\StateManagement;

function createState (string $stateKey, $default): StateManagement
{
   if (session_status() < 2) session_start();
   return new StateManagement($stateKey, $default);
}