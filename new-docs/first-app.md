# :rocket: Your First phpSPA App

Let's build a **complete todo application** to demonstrate phpSPA's core features: components, state management, routing, and more.

---

## :dart: **What We're Building**

A fully functional todo app with:

- ✅ Add/remove todos  
- ✅ Mark todos as complete
- ✅ Filter by status (all/active/completed)
- ✅ Persist state across page reloads
- ✅ Clean, responsive design

---

## :file_folder: **Project Structure**

```
todo-app/
├── index.php          # App entry point
├── layout.php         # HTML layout
├── components/
│   ├── TodoApp.php     # Main app component
│   ├── TodoList.php    # Todo list component
│   ├── TodoItem.php    # Individual todo item
│   └── TodoFilter.php  # Filter component
└── styles.css         # Basic styling
```

---

## :page_facing_up: **Step 1: Create the Layout**

Create `layout.php`:

```php
<?php
function layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>TodoApp - phpSPA</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                
                .header {
                    background: #4f46e5;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                
                .header h1 {
                    font-size: 2rem;
                    margin-bottom: 10px;
                }
                
                .add-todo {
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .add-todo input {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e5e7eb;
                    border-radius: 8px;
                    font-size: 16px;
                }
                
                .add-todo input:focus {
                    outline: none;
                    border-color: #4f46e5;
                }
                
                .filters {
                    display: flex;
                    justify-content: center;
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                    gap: 10px;
                }
                
                .filter-btn {
                    padding: 8px 16px;
                    border: 2px solid #e5e7eb;
                    background: white;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                .filter-btn.active {
                    background: #4f46e5;
                    color: white;
                    border-color: #4f46e5;
                }
                
                .todo-item {
                    display: flex;
                    align-items: center;
                    padding: 15px 20px;
                    border-bottom: 1px solid #f3f4f6;
                    transition: background 0.2s;
                }
                
                .todo-item:hover {
                    background: #f9fafb;
                }
                
                .todo-item.completed {
                    opacity: 0.6;
                    text-decoration: line-through;
                }
                
                .todo-checkbox {
                    margin-right: 12px;
                    transform: scale(1.2);
                }
                
                .todo-text {
                    flex: 1;
                    font-size: 16px;
                }
                
                .delete-btn {
                    background: #ef4444;
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .empty-state {
                    text-align: center;
                    padding: 40px;
                    color: #6b7280;
                }
            </style>
        </head>
        <body>
            <div id="app"></div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
        </body>
        </html>
    HTML;
}
```

---

## :puzzle: **Step 2: Build Components**

### Main TodoApp Component

Create `components/TodoApp.php`:

```php
<?php
use function Component\createState;

require_once 'TodoList.php';
require_once 'TodoFilter.php';

function TodoApp() {
    $todos = createState('todos', []);
    $filter = createState('filter', 'all');
    
    return <<<HTML
        <div class="container">
            <div class="header">
                <h1>📝 Todo App</h1>
                <p>Built with phpSPA</p>
            </div>
            
            <div class="add-todo">
                <input 
                    type="text" 
                    id="todoInput" 
                    placeholder="What needs to be done?"
                    onkeypress="if(event.key==='Enter') addTodo()"
                >
            </div>
            
            {TodoFilter()}
            {TodoList()}
            
            <script data-type="phpspa/script">
                function addTodo() {
                    const input = document.getElementById('todoInput');
                    const text = input.value.trim();
                    
                    if (text) {
                        const currentTodos = {$todos->toJson()};
                        const newTodo = {
                            id: Date.now(),
                            text: text,
                            completed: false
                        };
                        
                        phpspa.setState('todos', [...currentTodos, newTodo]);
                        input.value = '';
                    }
                }
            </script>
        </div>
    HTML;
}
```

### TodoList Component

Create `components/TodoList.php`:

```php
<?php
use function Component\createState;

require_once 'TodoItem.php';

function TodoList() {
    $todos = createState('todos', []);
    $filter = createState('filter', 'all');
    
    // Filter todos based on current filter
    $filteredTodos = array_filter($todos->get(), function($todo) use ($filter) {
        switch ($filter->get()) {
            case 'active': return !$todo['completed'];
            case 'completed': return $todo['completed'];
            default: return true;
        }
    });
    
    if (empty($filteredTodos)) {
        $emptyMessage = match($filter->get()) {
            'active' => 'No active todos! 🎉',
            'completed' => 'No completed todos yet.',
            default => 'No todos yet. Add one above! 👆'
        };
        
        return <<<HTML
            <div class="empty-state">
                <p>{$emptyMessage}</p>
            </div>
        HTML;
    }
    
    $todoItems = array_map(function($todo) {
        return TodoItem($todo);
    }, $filteredTodos);
    
    return '<div class="todo-list">' . implode('', $todoItems) . '</div>';
}
```

### TodoItem Component

Create `components/TodoItem.php`:

```php
<?php
use function Component\createState;

function TodoItem($todo) {
    $todos = createState('todos', []);
    $todoId = $todo['id'];
    $isCompleted = $todo['completed'];
    $completedClass = $isCompleted ? 'completed' : '';
    
    return <<<HTML
        <div class="todo-item {$completedClass}">
            <input 
                type="checkbox" 
                class="todo-checkbox"
                {($isCompleted ? 'checked' : '')}
                onchange="toggleTodo({$todoId})"
            >
            <span class="todo-text">{$todo['text']}</span>
            <button class="delete-btn" onclick="deleteTodo({$todoId})">
                Delete
            </button>
            
            <script data-type="phpspa/script">
                function toggleTodo(id) {
                    const currentTodos = {$todos->toJson()};
                    const updatedTodos = currentTodos.map(todo => 
                        todo.id === id ? {...todo, completed: !todo.completed} : todo
                    );
                    phpspa.setState('todos', updatedTodos);
                }
                
                function deleteTodo(id) {
                    const currentTodos = {$todos->toJson()};
                    const filteredTodos = currentTodos.filter(todo => todo.id !== id);
                    phpspa.setState('todos', filteredTodos);
                }
            </script>
        </div>
    HTML;
}
```

### TodoFilter Component

Create `components/TodoFilter.php`:

```php
<?php
use function Component\createState;

function TodoFilter() {
    $filter = createState('filter', 'all');
    $todos = createState('todos', []);
    
    $activeCount = count(array_filter($todos->get(), fn($todo) => !$todo['completed']));
    $completedCount = count(array_filter($todos->get(), fn($todo) => $todo['completed']));
    
    $filters = [
        'all' => "All ({count($todos->get())})",
        'active' => "Active ({$activeCount})",  
        'completed' => "Completed ({$completedCount})"
    ];
    
    $filterButtons = array_map(function($key, $label) use ($filter) {
        $activeClass = $filter->get() === $key ? 'active' : '';
        return <<<HTML
            <button 
                class="filter-btn {$activeClass}"
                onclick="phpspa.setState('filter', '{$key}')"
            >
                {$label}
            </button>
        HTML;
    }, array_keys($filters), $filters);
    
    return <<<HTML
        <div class="filters">
            {implode('', $filterButtons)}
        </div>
    HTML;
}
```

---

## :gear: **Step 3: Set Up the App**

Create `index.php`:

```php
<?php
require 'vendor/autoload.php';
require 'layout.php';
require 'components/TodoApp.php';

use phpSPA\App;
use phpSPA\Component;

// Create app with layout
$app = new App('layout');

// Register TodoApp component
$todoApp = new Component('TodoApp');
$todoApp->route('/');

// Run the application
$app->attach($todoApp)->run();
```

---

## :test_tube: **Step 4: Test Your App**

Start the development server:

```bash
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) and test:

1. ✅ **Add todos** by typing and pressing Enter
2. ✅ **Mark complete** by clicking checkboxes  
3. ✅ **Filter todos** using the filter buttons
4. ✅ **Delete todos** using the delete button
5. ✅ **Refresh page** — state persists!

---

## :sparkles: **Step 5: Add Advanced Features**

### Bulk Actions

Add to `TodoApp.php`:

```php
// Add after the add-todo div
<div class="bulk-actions" style="padding: 10px 20px; border-bottom: 1px solid #e5e7eb;">
    <button onclick="markAllComplete()" style="margin-right: 10px;">
        Mark All Complete
    </button>
    <button onclick="clearCompleted()">
        Clear Completed
    </button>
</div>

<script data-type="phpspa/script">
    function markAllComplete() {
        const currentTodos = {$todos->toJson()};
        const updatedTodos = currentTodos.map(todo => ({...todo, completed: true}));
        phpspa.setState('todos', updatedTodos);
    }
    
    function clearCompleted() {
        const currentTodos = {$todos->toJson()};
        const activeTodos = currentTodos.filter(todo => !todo.completed);
        phpspa.setState('todos', activeTodos);
    }
</script>
```

### Local Storage Persistence

Add to the main TodoApp script:

```javascript
// Save to localStorage whenever todos change
phpspa.on('stateChange', function(key, value) {
    if (key === 'todos') {
        localStorage.setItem('phpSPA_todos', JSON.stringify(value));
    }
});

// Load from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTodos = localStorage.getItem('phpSPA_todos');
    if (savedTodos) {
        phpspa.setState('todos', JSON.parse(savedTodos));
    }
});
```

---

## :trophy: **What You've Learned**

Congratulations! You've built a complete todo application using phpSPA. You've learned:

- ✅ **Component composition** — building UIs from small, reusable pieces
- ✅ **State management** — reactive state that updates the UI automatically  
- ✅ **Event handling** — responding to user interactions
- ✅ **Conditional rendering** — showing different content based on state
- ✅ **Data filtering** — processing state to display subsets
- ✅ **PHP-JavaScript integration** — seamless communication between server and client

---

## :books: **Next Steps**

### :material-rocket: **Enhance Your App**

- Add **due dates** and **priorities**
- Implement **drag-and-drop** reordering
- Add **categories** or **tags**
- Build **search functionality**

### :material-school: **Learn More Concepts**

- [**Routing**](concepts/routing.md) — Add multiple pages
- [**Security**](security/csrf.md) — Secure forms and data
- [**Performance**](performance/compression.md) — Optimize for production
- [**Advanced Features**](advanced/php-js-bridge.md) — PHP-JS communication

---

!!! success "You're a phpSPA Developer!"
    You now understand the fundamentals of building reactive PHP applications. The patterns you've learned here scale to much larger applications.
