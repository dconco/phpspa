# Class Components

## Overview

phpSPA v1.1.5 introduces support for PHP class components, allowing you to organize your components using object-oriented programming principles. This feature supports both regular classes and namespaced classes, providing better code organization and reusability.

## Key Features

-  **Class-based Components**: Use PHP classes as components
-  **Namespace Support**: Full namespace compatibility (`<Namespace.Class />`)
-  **Required `__render` Method**: Standardized rendering interface
-  **Props Support**: Pass data to class components
-  **State Management**: Class-level state handling
-  **Method Access**: Call class methods from templates

## Basic Class Component

### Simple Class Component

```php
<?php
class UserCard {
    public function __render($name, $email) {
        return <<<HTML
           <div class="user-card">
               <h3>{$name}</h3>
               <p>{$email}</p>
           </div>
        HTML;
    }
}
```

### Usage in Templates

```php
<?php
// Using the class component
echo '<UserCard name="John Doe" email="john@example.com" />';
```

## Namespaced Components

### Define Namespaced Component

```php
<?php
namespace Components\UI;

class Button {
    public function __render($text, $class, $onclick = '') {
        return <<<HTML
           <button class="{$class}" onclick="{$onclick}">
               {$text}
           </button>
        HTML;
    }
}
```

### Usage with Namespace

```php
<?php
// Using namespaced component
echo '<Components.UI.Button text="Submit" class="btn-primary" />';
```

## Advanced Class Components

### Component with State

```php
<?php
namespace Components;

use phpSPA\Http\Request;

class Counter {
    private $count;

    public function __construct() {
    }

    public function __render(Request $request, $id) {
        $this->count = $request('count', 0);

        return <<<HTML
           <div class="counter" id="{$id}">
               <h3>Count: {$this->count}</h3>
               <button onclick="this.incrementCount()">+</button>
               <button onclick="this.decrementCount()">-</button>
           </div>
           <script>
               class CounterComponent {
                   incrementCount() {
                       phpspa.setState('count', {$this->count} + 1);
                   }

                   decrementCount() {
                       phpspa.setState('count', Math.max(0, {$this->count} - 1));
                   }
               }

               const counter = new CounterComponent();
               window.incrementCount = () => counter.incrementCount();
               window.decrementCount = () => counter.decrementCount();
           </script>
        HTML;
    }
}
```

### Component with Methods

```php
<?php
class DataTable {
    private $data;
    private $columns;

    public function __render($id = 'data-table', $data = [], $columns = []) {
        $this->data = $data;
        $this->columns = $columns;
        $tableId = $props['id'] ?? 'data-table';

        return <<<HTML
           <div class="data-table-container">
               {$this->renderTable($tableId)}
               {$this->renderPagination()}
           </div>
        HTML;
    }

    private function renderTable($id) {
        $headers = $this->renderHeaders();
        $rows = $this->renderRows();

        return <<<HTML
           <table id="{$id}" class="data-table">
               <thead>{$headers}</thead>
               <tbody>{$rows}</tbody>
           </table>
        HTML;
    }

    private function renderHeaders() {
        $headers = '';
        foreach ($this->columns as $column) {
            $headers .= "<th>{$column['title']}</th>";
        }
        return $headers;
    }

    private function renderRows() {
        $rows = '';
        foreach ($this->data as $row) {
            $rows .= '<tr>';
            foreach ($this->columns as $column) {
                $value = $row[$column['key']] ?? '';
                $rows .= "<td>{$value}</td>";
            }
            $rows .= '</tr>';
        }
        return $rows;
    }

    private function renderPagination() {
        return <<<HTML
           <div class="pagination">
               <button onclick="this.previousPage()">Previous</button>
               <button onclick="this.nextPage()">Next</button>
           </div>
        HTML;
    }
}
```

## Component with Props Validation

```php
<?php
namespace Components\Forms;

use function Component\HTMLAttrInArrayToString;

class FormInput {
    private $requiredProps = ['name', 'type'];
    private $defaultProps = [
        'type' => 'text',
        'class' => 'form-control',
        'required' => false
    ];

    public function __render(...$props) {
        $props = $this->validateAndMergeProps($props);

        $attributes = HTMLAttrInArrayToString($props);

        return <<<HTML
           <div class="form-group">
               {$this->renderLabel($props)}
               <input {$attributes} />
               {$this->renderError($props)}
           </div>
        HTML;
    }

    private function validateAndMergeProps($props) {
        // Check required props
        foreach ($this->requiredProps as $required) {
            if (!isset($props[$required])) {
                throw new InvalidArgumentException("Required prop '{$required}' is missing");
            }
        }

        // Merge with defaults
        return array_merge($this->defaultProps, $props);
    }

    private function renderLabel($props) {
        if (isset($props['label'])) {
            $for = $props['name'];
            $label = htmlspecialchars($props['label']);
            return "<label for=\"{$for}\">{$label}</label>";
        }
        return '';
    }

    private function renderError($props) {
        if (isset($props['error'])) {
            $error = htmlspecialchars($props['error']);
            return "<div class=\"error-message\">{$error}</div>";
        }
        return '';
    }
}
```

### With CSRF Protection

```php
<?php
namespace Components\Auth;

class LoginForm {
    public function __render($action = '/login', $method = 'POST') {
        return <<<HTML
           <form class="login-form" action="{$action}" method="{$method}">
               <Component.Csrf name="login-form" />

               <div class="form-group">
                   <label for="username">Username</label>
                   <input type="text" id="username" name="username" required>
               </div>

               <div class="form-group">
                   <label for="password">Password</label>
                   <input type="password" id="password" name="password" required>
               </div>

               <button type="submit" class="btn-primary">Login</button>
           </form>
        HTML;
    }
}
```

## Best Practices

### 1. Component Organization

```php
<?php
// Organize components by feature
namespace Components\User;
namespace Components\Admin;
namespace Components\Common;

// Use descriptive class names
class UserProfileCard {}  // Good
class Card {}             // Too generic
```

### 2. Props Handling

```php
<?php
class Component {
    public function __render(...$props) {
        // Always provide defaults
        $title = $props['title'] ?? 'Default Title';

        // Validate critical props
        if (!isset($props['id'])) {
            throw new InvalidArgumentException('ID is required');
        }

        // Sanitize user input
        $userInput = htmlspecialchars($props['userInput'] ?? '');

        return "<!-- component HTML -->";
    }
}
```

### 3. Method Organization

```php
<?php
class Component {
    // Public render method
    public function __render($props) {
        return $this->buildComponent($props);
    }

    // Private helper methods
    private function buildComponent($props) {
        return $this->renderHeader($props) . $this->renderBody($props);
    }

    private function renderHeader($props) {
        // Header rendering logic
    }

    private function renderBody($props) {
        // Body rendering logic
    }
}
```

### 4. Error Handling

```php
<?php
class Component {
    public function __render($props) {
        try {
            return $this->safeRender($props);
        } catch (Exception $e) {
            error_log("Component render error: " . $e->getMessage());
            return $this->renderError("Component failed to load");
        }
    }

    private function safeRender($props) {
        // Component logic that might throw
    }

    private function renderError($message) {
        return "<div class=\"component-error\">{$message}</div>";
    }
}
```

## Migration from Function Components

### Before (Function Component)

```php
<?php
function UserCard(...$props) {
    $name = $props['name'] ?? 'Unknown';
    return "<div class=\"user-card\"><h3>{$name}</h3></div>";
}
```

### After (Class Component)

```php
<?php
class UserCard {
    public function __render(...$props) {
        $name = $props['name'] ?? 'Unknown';
        return "<div class=\"user-card\"><h3>{$name}</h3></div>";
    }
}
```

### Usage Remains the Same

```php
<?php
// Both work identically
echo '<UserCard name="John Doe" />';
```

## Testing Class Components

```php
<?php
class TestableComponent {
    public function __render(...$props) {
        return $this->buildOutput($props);
    }

    // Make methods testable
    public function buildOutput($props) {
        return "<div>{$props['content']}</div>";
    }
}

// Test
$component = new TestableComponent();
$result = $component->buildOutput(['content' => 'test']);
assert($result === '<div>test</div>');
```

## Performance Considerations

### 1. Component Caching

```php
<?php
class CachedComponent {
    private static $cache = [];

    public function __render(...$props) {
        $key = md5(serialize($props));

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $result = $this->buildComponent($props);
        self::$cache[$key] = $result;

        return $result;
    }
}
```

### 2. Lazy Loading

```php
<?php
class LazyComponent {
    private $data;

    public function __render(...$props) {
        // Load data only when needed
        if ($this->data === null) {
            $this->data = $this->loadData($props);
        }

        return $this->renderWithData($this->data);
    }
}
```

Class components provide a powerful way to organize your phpSPA applications with better structure, reusability, and maintainability while maintaining full compatibility with existing function-based components.
