<?php

namespace Component;

use Closure;
use InvalidArgumentException;
use PhpSPA\Core\Helper\CallableInspector;
use PhpSPA\Core\Helper\StateManager;

/**
 * Executes a callback function when its dependencies change.
 *
 * This hook mimics React's useEffect. It observes an array of state
 * dependencies and runs the provided callback only once per render cycle
 * if any of the dependency values have changed since the previous render.
 *
 * @package Component
 * @author dconco <me@dconco.tech>
 * @param Closure        $callback     The function to execute when a change is detected.
 * @param StateManager[] $dependencies An array of state objects to watch for changes.
 * @return void
 * @throws InvalidArgumentException If a dependency is not an instance of StateManager.
 * @see https://phpspa.tech/hooks/use-effect
 */
function useEffect(Closure $callback, array $dependencies = []): void
{
   $firstRender = CallableInspector::getProperty(StateManager::class, 'firstRender');
   
   if (empty($dependencies) && $firstRender === true) {
      $callback();
      return;
   }

   $stateDependencies = array_filter($dependencies, fn ($d) => $d instanceof StateManager);

   foreach ($dependencies as $dep) {
      if (is_array($dep) && empty($dep)) {
         if ($firstRender === true) {
            $callback(...$stateDependencies);
            return;
         }
         continue;
      }

      if (!$dep instanceof StateManager) {
         throw new InvalidArgumentException("All dependencies must be instances of StateManager.");
      }

      $stateValue = $dep();
      $lastValue = CallableInspector::getProperty($dep, 'lastState');

      if ($stateValue !== $lastValue) {
         $callback(...$stateDependencies);
         return;
      }
   }
}