## Updating State of Mapped Arrays

State becomes truly useful when you modify it based on user actions, like clicking a button. This is done using the client-side `setState()` function.

You can update state in two primary ways: a quick inline method for simple changes, and a more robust script-based method for complex logic.

-----

### The Direct Way: Inline Updates

For simple actions like adding a new item to a list, you can call `setState()` directly within an HTML attribute like `onclick`.

This approach uses the **JavaScript spread syntax (`...`)** to create a new array that includes all the old items plus the new one.

```php
<?php
use function Component\useState;

function TodoList() {
   $todos = useState('todos', [
      ['id' => 1, 'text' => 'Learn phpspa'],
   ]);
   
   $newId = count($todos()) + 1;

   return <<<HTML
      <div>
         <h3>My To-Do List</h3>
         <ul>
            {$todos->map(fn($item) => "<li>{$item['text']}</li>")}
         </ul>
         
         <button onclick='setState("todos", [...{$todos}, { id: {$newId}, text: "A new task" }])'>
            Add Todo
         </button>
      </div>
   HTML;
}
```

-----

### The Customizable Way: Using a Script

When your update logic is more complex (e.g., reading from input fields), it's cleaner to use a dedicated JavaScript function.

First, pass your initial state to a JavaScript variable. Then, you can manipulate this variable with standard JavaScript before calling `setState()` with the final data.

```php
<?php
use function Component\useState;

function TodoList() {
   $todos = useState('todos', [
      ['id' => 1, 'text' => 'Learn phpspa'],
   ]);

   return <<<HTML
      <div>
         <h3>My To-Do List</h3>
         <ul id="todo-list-ul">
            {$todos->map(fn($item) => "<li>{$item['text']}</li>")}
         </ul>
         
         <button onclick="addTodo()">Add Todo</button>
      </div>

      <script>
         // Get the initial state from PHP
         let todosData = {$todos};

         function addTodo() {
            // Perform any logic you need in JavaScript
            let newId = todosData.length + 1;
            todosData.push({ id: newId, text: 'New task ' + newId });

            // Update the state with the modified array
            setState('todos', todosData);
         }
      </script>
   HTML;
}
```
