# Handling Side Effects with `useEffect`

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

Sometimes you need to perform actions *after* your component has rendered, like fetching data from an API, logging to the console, or manually manipulating the DOM. These actions are called "side effects," and the `useEffect` hook is the perfect tool for managing them.

!!! info "Effect Hook"
    The hook takes two arguments: a callback function to run and an array of dependencies to watch.

## Basic Usage: Watching for Changes

The most common use case is to run code whenever a specific piece of state changes.

```php
<?php

use function Component\useState;
use function Component\useEffect;

function Counter() {
   $count = useState('count', 0);
   $script = '';

   // This effect will run every time the 'count' state changes.
   useEffect(function ($count) use (&$script) {
      // This script runs on the client-side after the re-render.
       $script = "<script>console.log('The counter is now: {$count}')</script>";
   }, [$count]);

   return <<<HTML
      <h2>Counter Value: {$count}</h2>
      <button onclick="setState('count', {$count} + 1)">
         Click to Increment
      </button>
       {$script}
   HTML;
}
```

!!! example "Side Effect"
    This effect will run every time the 'count' state changes.

## Controlling the Effect: The Dependency Array

The second argument to `useEffect` is the dependency array. It tells PhpSPA **when** to run your effect.

=== "Specific State Changes"

    Provide an array of state variables. The effect will run on the initial render and then again anytime one of those variables changes.

    ```php
    <?php

    useEffect($myCallback, [$stateA, $stateB]);
    ```

=== "Run Once on Load"

    Provide an **empty array `[]`**. The effect will only run once when the component is first loaded. This is perfect for initial setup tasks, like fetching data from an API.

    ```php
    <?php

    useEffect(function () use (&$script) {
       // This runs only once.
       $script = "<script>console.log('Component has loaded!')</script>";
    }, []);
    ```

!!! tip "Dependency Control"
    Use an empty array `[]` for effects that should only run once during component initialization.

## Updating State Inside an Effect

You can also update state from within an effect. This is useful for creating more complex, reactive logic.

In this example, when the counter changes, the effect calculates a new value and immediately updates the state again.

```php
<?php

function EffectExample() {
   $counter = useState('counter', 0);
   $message = useState('message', 'Waiting for an update...');

   useEffect(function ($counter) use ($message) {
      // Calculate a new value based on the current state
      $newCounterValue = $counter() + 1;
      $newMessage = "Counter was {$counter}, but the effect changed it to {$newCounterValue}!";
      
      // Update the state from within the effect
      $counter($newCounterValue);
      $message($newMessage);

   }, [$counter]); // This effect depends on the counter

   return <<<HTML
      <div>
         <p>{$message}</p>
         <button onclick="setState('counter', {$counter} + 1)">Trigger Effect</button>
      </div>
   HTML;
}
```

!!! success "Reactive Logic"
    In this example, when the counter changes, the effect calculates a new value and immediately updates the state again.
