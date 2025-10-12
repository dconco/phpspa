<?php

namespace Component;

use Closure;
use InvalidArgumentException;
use phpSPA\Core\Helper\CallableInspector;
use phpSPA\Core\Helper\StateManager;

/**
 * Executes a callback function when its dependencies change.
 *
 * This hook mimics React's useEffect. It observes an array of state
 * dependencies and runs the provided callback only once per render cycle
 * if any of the dependency values have changed since the previous render.
 *
 * @author dconco <concodave@gmail.com>
 * @param Closure        $callback     The function to execute when a change is detected.
 * @param StateManager[] $dependencies An array of state objects to watch for changes.
 * @return void
 * @throws InvalidArgumentException If a dependency is not an instance of StateManager.
 */
function useEffect(Closure $callback, array $dependencies = []): void
{
   foreach ($dependencies as $dep) {
      if (!$dep instanceof StateManager) {
         throw new InvalidArgumentException("All dependencies must be instances of StateManager.");
      }

      $stateValue = $dep();
      $lastValue = CallableInspector::getProperty($dep, 'lastState');

      if ($stateValue !== $lastValue) {
         $callback();
         return;
      }
   }
}