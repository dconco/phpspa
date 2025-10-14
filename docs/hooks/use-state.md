# Managing State with `useState`

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

State is the data in your component that can change over time. When state changes, PhpSPA automatically re-renders the component to reflect the new data. This is the key to creating dynamic and interactive UIs.

!!! info "The useState Hook"
    The `useState` hook is how you add state to your components.

## A Simple Counter

Here's the classic counter example. The `useState` hook creates a state variable named `count` with an initial value of `0`.

```php
<?php
use function Component\useState;

function Counter() {
   $count = useState('count', 0);

   // The client-side setState() function updates the state and triggers a re-render.
   return <<<HTML
      <div>
         <h2>Counter Value: {$count}</h2>
         <button onclick="setState('count', {$count} + 1)">
            Click to Increment
         </button>
      </div>
   HTML;
}
```

!!! tip "Understanding State Variables"
    - `useState('count', 0)`: Declares a piece of state named `count` and sets its default value to `0`
    - `{$count}`: When used in a string, the state variable automatically outputs its current value
    - `setState('count', ...)`: This is a global JavaScript function provided by PhpSPA to update state from the browser

## Rendering Lists with `map()`

If your state holds an array, you can use the powerful `->map()` method to iterate over it and render a list of elements.

This is perfect for rendering dynamic data, like a list of tasks.

```php
<?php
use function Component\useState;

function TodoList() {
   $todos = useState('todos', [
      ['id' => 1, 'text' => 'Learn phpspa'],
      ['id' => 2, 'text' => 'Build an awesome app'],
      ['id' => 3, 'text' => 'Deploy to production']
   ]);

   return <<<HTML
      <div>
         <h3>My To-Do List</h3>
         <ul>
            {$todos->map(fn($item) => "<li>{$item['text']}</li>")}
         </ul>
      </div>
   HTML;
}
```

!!! success "Dynamic Rendering"
    The `->map()` method loops through each item in the `$todos` array and runs your function, concatenating the resulting HTML strings into a final list.
