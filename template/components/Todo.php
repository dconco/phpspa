<?php

use PhpSPA\Component;
use PhpSPA\Http\Session;

use function Component\useEffect;
use function Component\useFetch;
use function Component\useState;

function TodoList ()
{
   $response = useFetch('https://jsonplaceholder.typicode.com/users');
   echo($response->get()->text());
   exit;


   $initialTodos = Session::get('todos') ?: [
      [ 'id' => 1, 'text' => 'Learn phpspa' ],
      [ 'id' => 2, 'text' => 'Build an awesome app' ],
      [ 'id' => 3, 'text' => 'Deploy to production' ]
   ];
   $todos = useState('todos', $initialTodos);

   useEffect(fn ($todos) => Session::set('todos', $todos()), [ $todos ]);

   return <<<HTML
      <div>
         <h3>My To-Do List</h3>
         <ul>
            {$todos->map(fn ($item) => "<li>{$item['text']}</li>\n")}
         </ul>

         <button onclick="addTodo()">Add Todo</button>

         <script>
            let todosData = {$todos};

            function addTodo() {
               const value = prompt('Enter new todo:');

               if (value) {
                  let newId = todosData.length + 1;
                  todosData.push({ id: newId, text: value });

                  setState('todos', todosData);
               }
            }
         </script>
      </div>
   HTML;
}

return (new Component('TodoList'))
   ->route('/todo');