# ⚡ Quick Start - Build Your First phpSPA App

Get a working phpSPA application running in **under 5 minutes**. This tutorial will walk you through creating a complete, interactive application from scratch.

!!! tip "What You'll Build"
    
    A **Todo App** with:
    
    - ✅ Interactive components
    - ✅ Reactive state management  
    - ✅ SPA navigation
    - ✅ Real-time updates

---

## 🚀 Step 1: Installation

Choose your preferred installation method:

=== "Composer (Recommended)"

    ```bash
    # Create new project
    mkdir my-phpspa-app
    cd my-phpspa-app
    
    # Install phpSPA
    composer require dconco/phpspa
    
    # Ready to code!
    ```

=== "Template Project"

    ```bash
    # Clone complete template
    git clone https://github.com/mrepol742/phpspa-example my-phpspa-app
    cd my-phpspa-app
    
    # Install dependencies
    composer install
    
    # Start development server
    composer start
    ```

=== "Manual Download"

    ```bash
    # Download from GitHub
    git clone https://github.com/dconco/phpspa.git
    cd phpspa
    
    # Copy to your project
    cp -r app/ /path/to/your/project/
    ```

---

## 🏗️ Step 2: Create the Layout

Create `Layout.php` - your application's base template:

```php title="Layout.php"
<?php
function Layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>My Todo App</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 2rem;
                    background: #f5f5f5;
                }
                
                .container {
                    background: white;
                    padding: 2rem;
                    border-radius: 1rem;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                }
                
                nav {
                    display: flex;
                    gap: 1rem;
                    margin-bottom: 2rem;
                    padding-bottom: 1rem;
                    border-bottom: 2px solid #eee;
                }
                
                nav a {
                    text-decoration: none;
                    padding: 0.5rem 1rem;
                    background: #667eea;
                    color: white;
                    border-radius: 0.5rem;
                    transition: all 0.2s ease;
                }
                
                nav a:hover {
                    background: #5a6fd8;
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <nav>
                    <Component.Link to="/" label="📝 Todos" />
                    <Component.Link to="/about" label="ℹ️ About" />
                    <Component.Link to="/stats" label="📊 Stats" />
                </nav>
                
                <main id="app">
                    <!-- Your components render here -->
                </main>
            </div>
            
            <!-- phpSPA JavaScript Engine -->
            <script src="https://unpkg.com/phpspa-js"></script>
        </body>
        </html>
    HTML;
}
```

!!! info "Layout Structure"
    
    - **Navigation**: Using `<Component.Link />` for SPA navigation
    - **Target Container**: `#app` where components will render
    - **JavaScript Engine**: CDN link to phpSPA client library

---

## 🧩 Step 3: Create Components

### Todo List Component

Create `components/TodoApp.php`:

```php title="components/TodoApp.php"
<?php
use function phpSPA\Component\createState;

function TodoApp() {
    // Reactive state for todos and input
    $todos = createState('todos', [
        ['id' => 1, 'text' => 'Learn phpSPA', 'completed' => true],
        ['id' => 2, 'text' => 'Build awesome apps', 'completed' => false]
    ]);
    
    $newTodo = createState('newTodo', '');
    $filter = createState('filter', 'all'); // all, active, completed
    
    // Filter todos based on current filter
    $filteredTodos = array_filter($todos, function($todo) use ($filter) {
        return match($filter) {
            'active' => !$todo['completed'],
            'completed' => $todo['completed'],
            default => true
        };
    });
    
    // Generate todo items HTML
    $todoItems = array_map(function($todo) {
        $checkedClass = $todo['completed'] ? 'completed' : '';
        $checked = $todo['completed'] ? 'checked' : '';
        
        return <<<HTML
            <li class="todo-item {$checkedClass}">
                <input 
                    type="checkbox" 
                    {$checked}
                    onchange="toggleTodo({$todo['id']})"
                >
                <span class="todo-text">{$todo['text']}</span>
                <button class="delete-btn" onclick="deleteTodo({$todo['id']})">
                    🗑️
                </button>
            </li>
        HTML;
    }, $filteredTodos);
    
    $todoList = implode('', $todoItems);
    $totalTodos = count($todos);
    $completedTodos = count(array_filter($todos, fn($t) => $t['completed']));
    $activeTodos = $totalTodos - $completedTodos;
    
    return <<<HTML
        <div class="todo-app">
            <h1>📝 My Todo App</h1>
            
            <!-- Add new todo form -->
            <div class="add-todo">
                <input 
                    type="text"
                    placeholder="What needs to be done?"
                    value="{$newTodo}"
                    onkeyup="handleNewTodoInput(event)"
                    class="new-todo-input"
                >
                <button onclick="addTodo()" class="add-btn">
                    ➕ Add Todo
                </button>
            </div>
            
            <!-- Filter buttons -->
            <div class="filters">
                <button 
                    class="filter-btn {$filter === 'all' ? 'active' : ''}"
                    onclick="setFilter('all')"
                >
                    All ({$totalTodos})
                </button>
                <button 
                    class="filter-btn {$filter === 'active' ? 'active' : ''}"
                    onclick="setFilter('active')"
                >
                    Active ({$activeTodos})
                </button>
                <button 
                    class="filter-btn {$filter === 'completed' ? 'active' : ''}"
                    onclick="setFilter('completed')"
                >
                    Completed ({$completedTodos})
                </button>
            </div>
            
            <!-- Todo list -->
            <ul class="todo-list">
                {$todoList}
            </ul>
            
            <!-- Stats -->
            <div class="stats">
                <p>📊 <strong>{$activeTodos}</strong> items left</p>
                {$completedTodos > 0 ? "<button onclick='clearCompleted()' class='clear-btn'>Clear Completed</button>" : ""}
            </div>
        </div>
        
        <!-- Component Styles -->
        <style data-type="phpspa/css">
            .todo-app {
                max-width: 600px;
                margin: 0 auto;
            }
            
            .add-todo {
                display: flex;
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .new-todo-input {
                flex: 1;
                padding: 1rem;
                border: 2px solid #ddd;
                border-radius: 0.5rem;
                font-size: 1rem;
            }
            
            .new-todo-input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .add-btn {
                padding: 1rem 1.5rem;
                background: #4caf50;
                color: white;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            
            .add-btn:hover {
                background: #45a049;
                transform: translateY(-2px);
            }
            
            .filters {
                display: flex;
                gap: 0.5rem;
                margin-bottom: 2rem;
                justify-content: center;
            }
            
            .filter-btn {
                padding: 0.5rem 1rem;
                border: 2px solid #ddd;
                background: white;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .filter-btn.active {
                background: #667eea;
                color: white;
                border-color: #667eea;
            }
            
            .filter-btn:hover:not(.active) {
                border-color: #667eea;
                color: #667eea;
            }
            
            .todo-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .todo-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
                background: #f9f9f9;
                margin-bottom: 0.5rem;
                border-radius: 0.5rem;
                transition: all 0.2s ease;
            }
            
            .todo-item:hover {
                background: #f0f0f0;
                transform: translateX(4px);
            }
            
            .todo-item.completed {
                opacity: 0.7;
            }
            
            .todo-item.completed .todo-text {
                text-decoration: line-through;
                color: #888;
            }
            
            .todo-text {
                flex: 1;
                font-size: 1rem;
            }
            
            .delete-btn {
                background: #f44336;
                color: white;
                border: none;
                padding: 0.5rem;
                border-radius: 0.25rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .delete-btn:hover {
                background: #d32f2f;
                transform: scale(1.1);
            }
            
            .stats {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 2px solid #eee;
            }
            
            .clear-btn {
                background: #ff9800;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .clear-btn:hover {
                background: #f57c00;
            }
        </style>
        
        <!-- Component Scripts -->
        <script data-type="phpspa/script">
            // Handle new todo input
            function handleNewTodoInput(event) {
                phpspa.setState('newTodo', event.target.value);
                
                // Add todo on Enter key
                if (event.key === 'Enter') {
                    addTodo();
                }
            }
            
            // Add new todo
            function addTodo() {
                const newTodoText = phpspa.getState('newTodo');
                
                if (newTodoText.trim()) {
                    const todos = phpspa.getState('todos');
                    const newTodo = {
                        id: Date.now(),
                        text: newTodoText.trim(),
                        completed: false
                    };
                    
                    phpspa.setState('todos', [...todos, newTodo]);
                    phpspa.setState('newTodo', '');
                }
            }
            
            // Toggle todo completion
            function toggleTodo(id) {
                const todos = phpspa.getState('todos');
                const updatedTodos = todos.map(todo => 
                    todo.id === id 
                        ? { ...todo, completed: !todo.completed }
                        : todo
                );
                
                phpspa.setState('todos', updatedTodos);
            }
            
            // Delete todo
            function deleteTodo(id) {
                const todos = phpspa.getState('todos');
                const filteredTodos = todos.filter(todo => todo.id !== id);
                
                phpspa.setState('todos', filteredTodos);
            }
            
            // Set filter
            function setFilter(filter) {
                phpspa.setState('filter', filter);
            }
            
            // Clear completed todos
            function clearCompleted() {
                const todos = phpspa.getState('todos');
                const activeTodos = todos.filter(todo => !todo.completed);
                
                phpspa.setState('todos', activeTodos);
            }
        </script>
    HTML;
}
```

### About Component

Create `components/About.php`:

```php title="components/About.php"
<?php
function About() {
    return <<<HTML
        <div class="about-page">
            <h1>ℹ️ About This App</h1>
            
            <div class="info-card">
                <h2>🧩 Built with phpSPA</h2>
                <p>
                    This Todo app demonstrates the power of <strong>phpSPA</strong> - 
                    a component-based PHP library that brings React-like development 
                    experience to server-side applications.
                </p>
            </div>
            
            <div class="info-card">
                <h2>✨ Features Showcased</h2>
                <ul>
                    <li>🔄 <strong>Reactive State Management</strong> - Real-time updates</li>
                    <li>🧩 <strong>Component Architecture</strong> - Reusable, maintainable code</li>
                    <li>🧭 <strong>SPA Navigation</strong> - No full page reloads</li>
                    <li>💫 <strong>Component Scripts & Styles</strong> - Scoped functionality</li>
                    <li>⚡ <strong>Performance Optimized</strong> - Built-in compression</li>
                </ul>
            </div>
            
            <div class="info-card">
                <h2>🚀 Get Started</h2>
                <p>Ready to build your own phpSPA application?</p>
                <div class="cta-buttons">
                    <a href="https://phpspa.readthedocs.io" target="_blank" class="cta-btn">
                        📚 Read Documentation
                    </a>
                    <a href="https://github.com/dconco/phpspa" target="_blank" class="cta-btn">
                        ⭐ Star on GitHub
                    </a>
                </div>
            </div>
        </div>
        
        <style data-type="phpspa/css">
            .about-page {
                max-width: 700px;
                margin: 0 auto;
            }
            
            .info-card {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                padding: 2rem;
                border-radius: 1rem;
                margin-bottom: 2rem;
                box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            }
            
            .info-card h2 {
                margin-top: 0;
                color: #333;
            }
            
            .info-card ul {
                padding-left: 1.5rem;
            }
            
            .info-card li {
                margin-bottom: 0.5rem;
                line-height: 1.6;
            }
            
            .cta-buttons {
                display: flex;
                gap: 1rem;
                margin-top: 2rem;
                flex-wrap: wrap;
            }
            
            .cta-btn {
                background: #667eea;
                color: white;
                padding: 1rem 2rem;
                text-decoration: none;
                border-radius: 0.5rem;
                font-weight: 600;
                transition: all 0.2s ease;
                display: inline-block;
            }
            
            .cta-btn:hover {
                background: #5a6fd8;
                transform: translateY(-2px);
                box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            }
        </style>
    HTML;
}
```

### Stats Component

Create `components/Stats.php`:

```php title="components/Stats.php"
<?php
use function phpSPA\Component\createState;

function Stats() {
    $todos = createState('todos', []);
    
    $totalTodos = count($todos);
    $completedTodos = count(array_filter($todos, fn($t) => $t['completed']));
    $activeTodos = $totalTodos - $completedTodos;
    $completionRate = $totalTodos > 0 ? round(($completedTodos / $totalTodos) * 100) : 0;
    
    return <<<HTML
        <div class="stats-page">
            <h1>📊 Todo Statistics</h1>
            
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-icon">📝</div>
                    <div class="stat-info">
                        <h2>{$totalTodos}</h2>
                        <p>Total Todos</p>
                    </div>
                </div>
                
                <div class="stat-card active">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h2>{$activeTodos}</h2>
                        <p>Active Todos</p>
                    </div>
                </div>
                
                <div class="stat-card completed">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h2>{$completedTodos}</h2>
                        <p>Completed</p>
                    </div>
                </div>
                
                <div class="stat-card completion">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h2>{$completionRate}%</h2>
                        <p>Completion Rate</p>
                    </div>
                </div>
            </div>
            
            <div class="progress-section">
                <h2>📈 Progress Overview</h2>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {$completionRate}%"></div>
                </div>
                <p class="progress-text">
                    You've completed <strong>{$completedTodos}</strong> out of <strong>{$totalTodos}</strong> todos!
                </p>
            </div>
            
            {$totalTodos === 0 ? "
                <div class='empty-state'>
                    <h3>🎯 No todos yet!</h3>
                    <p>Head over to the <Component.Link to='/' label='Todo List' /> to get started.</p>
                </div>
            " : ""}
        </div>
        
        <style data-type="phpspa/css">
            .stats-page {
                max-width: 800px;
                margin: 0 auto;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                margin-bottom: 3rem;
            }
            
            .stat-card {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                transition: all 0.2s ease;
            }
            
            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            }
            
            .stat-card.total {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .stat-card.active {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
            }
            
            .stat-card.completed {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
            }
            
            .stat-card.completion {
                background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
                color: white;
            }
            
            .stat-icon {
                font-size: 2.5rem;
            }
            
            .stat-info h2 {
                margin: 0;
                font-size: 2rem;
                font-weight: 700;
            }
            
            .stat-info p {
                margin: 0;
                opacity: 0.9;
                font-weight: 500;
            }
            
            .progress-section {
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                text-align: center;
            }
            
            .progress-bar {
                width: 100%;
                height: 1rem;
                background: #eee;
                border-radius: 0.5rem;
                overflow: hidden;
                margin: 1rem 0;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
                transition: width 0.3s ease;
            }
            
            .progress-text {
                margin-top: 1rem;
                font-size: 1.1rem;
                color: #666;
            }
            
            .empty-state {
                text-align: center;
                padding: 3rem;
                background: #f9f9f9;
                border-radius: 1rem;
                margin-top: 2rem;
            }
            
            .empty-state h3 {
                color: #666;
                margin-bottom: 1rem;
            }
        </style>
    HTML;
}
```

---

## 🔧 Step 4: Wire Everything Together

Create `index.php` to bootstrap your application:

```php title="index.php"
<?php
require_once 'vendor/autoload.php';

// Import required classes
use phpSPA\App;
use phpSPA\Component;

// Include your layout and components
require_once 'Layout.php';
require_once 'components/TodoApp.php';
require_once 'components/About.php';
require_once 'components/Stats.php';

// Create the application with method chaining
$app = (new App('Layout'))
    ->attach(
        (new Component('TodoApp'))
            ->route('/')
            ->title('📝 My Todo App')
            ->method('GET')
    )
    ->attach(
        (new Component('About'))
            ->route('/about')
            ->title('ℹ️ About - My Todo App')
            ->method('GET')
    )
    ->attach(
        (new Component('Stats'))
            ->route('/stats')
            ->title('📊 Stats - My Todo App')
            ->method('GET')
    )
    ->defaultTargetID('app')
    ->compression('auto')
    ->cors()
    ->run();
```

!!! tip "Method Chaining"
    
    phpSPA v1.1.5 supports fluent method chaining for cleaner, more expressive code.

---

## 🚀 Step 5: Run Your Application

### Development Server

```bash
# Using PHP built-in server
php -S localhost:8000

# Or using Composer script (if available)
composer start
```

### Open in Browser

Navigate to `http://localhost:8000` and see your Todo app in action!

---

## ✨ What You've Built

Congratulations! You've created a fully functional Todo application with:

<div class="grid cards" markdown>

-   **🔄 Reactive State**
    
    ---
    
    Real-time updates when adding, completing, or deleting todos

-   **🧩 Component Architecture**
    
    ---
    
    Reusable, maintainable components with scoped styles and scripts

-   **🧭 SPA Navigation**
    
    ---
    
    Smooth transitions between pages without full reloads

-   **📊 Interactive UI**
    
    ---
    
    Filtering, statistics, and dynamic progress tracking

</div>

---

## 🎯 Key Features Demonstrated

### State Management
- `createState()` for reactive data
- `phpspa.setState()` for updates from JavaScript
- Cross-component state sharing

### Component Patterns
- Scoped CSS with `<style data-type="phpspa/css">`
- Component scripts with `<script data-type="phpspa/script">`
- Event handling and user interactions

### Navigation
- `<Component.Link />` for SPA navigation
- Route parameter handling
- Page titles and metadata

### Performance
- Automatic compression and optimization
- Efficient DOM updates
- Component-level caching

---

## 🔧 Next Steps

Now that you have a working application, explore these advanced features:

<div class="buttons" markdown>
[Learn Component Patterns](../components/index.md){ .md-button }
[Master State Management](../state/index.md){ .md-button }
[Explore Routing](../routing/index.md){ .md-button }
[Performance Optimization](../performance/index.md){ .md-button }
</div>

---

## 💡 Pro Tips

!!! tip "Development Workflow"
    
    1. **Start Simple**: Begin with basic components and add complexity gradually
    2. **Use State Wisely**: Only create state for data that needs to be reactive
    3. **Component Scope**: Keep component styles and scripts scoped
    4. **Test Often**: Use the built-in development server for rapid iteration

!!! info "Performance Tips"
    
    - Enable compression in production: `->compression('auto')`
    - Use component-level caching for expensive operations
    - Optimize images and assets
    - Leverage browser caching with proper headers

!!! success "You're Ready!"
    
    You've successfully built your first phpSPA application! You now understand the core concepts and can build complex, interactive web applications with pure PHP.

---

**🎉 Well done! You're now a phpSPA developer.** Ready to build something amazing?
