# 🪝 phpSPA Hooks

## Overview

Hooks in phpSPA are reusable functions that encapsulate stateful logic and can be shared across multiple components. They follow a similar pattern to React hooks but are implemented in pure PHP, allowing you to create clean, modular, and maintainable state management solutions.

!!! tip "Key Concept"
Hooks are just PHP functions that use `createState()` and return state variables or objects that components can use directly.

## Basic Hook Pattern

The fundamental pattern for creating a phpSPA hook:

```php
function useHookName($param1 = defaultValue, $param2 = defaultValue) {
    $state = createState("unique_key", $initialValue);

    // Optional: Add helper methods or computed values

    return $state; // or return an array/object with multiple values
}
```

## State Behavior in Hooks

### Automatic String Conversion

When you use a state variable directly in a string context, phpSPA automatically converts it to a JSON string:

```php
function useCounter($initialValue = 0) {
    $count = createState("counter", $initialValue);
    return $count;
}

// In your component:
$counter = useCounter(5);
echo "Count: {$counter}"; // Outputs: "Count: 5" (automatically converted)
```

### Accessing Raw Values

To get the actual array/object value without conversion, call the state as a function:

```php
$counter = useCounter([1, 2, 3]);
$rawArray = $counter(); // Returns actual array [1, 2, 3]
$stringValue = $counter; // Returns string (JSON) when used in string context
```

## Built-in Hook Examples

### useCounter Hook

A simple counter with increment/decrement functionality:

```php
function useCounter($initialValue = 0, $step = 1) {
    $count = createState("counter", $initialValue);

    return $count;
}
```

Usage in component:

```php
function CounterComponent() {
    $counter = useCounter(0, 2);

    return <<<HTML
    <div>
        <h2>Counter: {$counter}</h2>
        <button onclick="setState('counter', {$counter} + 2)">Increment</button>
        <button onclick="setState('counter', {$counter} - 2)">Decrement</button>
        <button onclick="setState('counter', 0)">Reset</button>
    </div>
    HTML;
}
```

### useToggle Hook

Boolean state management with toggle functionality:

```php
function useToggle($initialValue = false, $stateKey = "toggle") {
    $isToggled = createState($stateKey, $initialValue);

    return $isToggled;
}
```

Usage:

```php
function ToggleComponent() {
    $isVisible = useToggle(false, "visibility");

    return <<<HTML
    <div>
        <button onclick="setState('visibility', !{$isVisible})">
            Toggle Content
        </button>
        <div style="display: {$isVisible} ? 'block' : 'none'">
            <p>This content can be toggled!</p>
        </div>
    </div>
    HTML;
}
```

### useArray Hook

Array state management with common operations:

```php
function useArray($initialArray = [], $stateKey = "array") {
    $array = createState($stateKey, $initialArray);

    return $array;
}
```

Usage:

```php
function TodoListComponent() {
    $todos = useArray([], "todos");
    $currentTodos = $todos(); // Get raw array

    return <<<HTML
    <div>
        <h3>Todo List ({$todos}.length items)</h3>
        <button onclick="
            let todoArr = $todos;
            setState('todos', [...todoArr, 'New Todo'])">
            Add Todo
        </button>
        <ul>
            <!-- Todos would be rendered here -->
        </ul>
    </div>
    HTML;
}
```

### useForm Hook

Form state management:

```php
function useForm($initialData = [], $stateKey = "form") {
    $formData = createState($stateKey, $initialData);

    return $formData;
}
```

Usage:

```php
function ContactForm() {
    $form = useForm([
        'name' => '',
        'email' => '',
        'message' => ''
    ], "contact_form");

    return <<<HTML
    <form method="post">
        <input
            name="name"
            value="{$form}.name"
            placeholder="Your Name"
            onchange="
            let currentForm = {$form};
            setState('contact_form', {...currentForm, name: this.value})"
        >
        <input
            name="email"
            value="{$form}.email"
            placeholder="Your Email"
            onchange="
            let currentForm = {$form};
            setState('contact_form', {...currentForm, email: this.value})"
        >
        <textarea
            name="message"
            placeholder="Your Message"
            onchange="
            let currentForm = {$form};
            setState('contact_form', {...currentForm, message: this.value})"
        >{$form}.message</textarea>
        <button type="submit">Send Message</button>
    </form>
    HTML;
}
```

## Advanced Hook Patterns

### Hook with Multiple Return Values

```php
function useCounterWithHistory($initialValue = 0) {
    $count = createState("counter_value", $initialValue);
    $history = createState("counter_history", [$initialValue]);

    return [
        'count' => $count,
        'history' => $history
    ];
}
```

Usage:

```php
function AdvancedCounter() {
    $counter = useCounterWithHistory(0);

    return <<<HTML
    <div>
        <h2>Count: {$counter['count']}</h2>
        <p>History: {$counter['history']}</p>
        <button onclick="
            const newCount = {$counter['count']} + 1;
            const historyCount = {$counter['history']};
            setState('counter_value', newCount);
            setState('counter_history', [...historyCount, newCount]);
        ">Increment</button>
    </div>
    HTML;
}
```

### Conditional Hook Usage

```php
function useAuth($checkSession = true) {
    if (!$checkSession) {
        return createState("guest_mode", true);
    }

    $user = createState("current_user", null);
    $isAuthenticated = createState("is_authenticated", false);

    return [
        'user' => $user,
        'isAuthenticated' => $isAuthenticated
    ];
}
```

## Hook Best Practices

### 1. Naming Convention

-  Always prefix custom hooks with `use`
-  Use descriptive names: `useCounter`, `useToggle`, `useForm`
-  Be consistent with naming patterns

### 2. State Keys

```php
// ✅ Good: Unique and descriptive state keys
function useUserProfile($userId) {
    $profile = createState("user_profile_{$userId}", null);
    return $profile;
}

// ❌ Bad: Generic state keys that might conflict
function useUserProfile($userId) {
    $profile = createState("profile", null); // Too generic
    return $profile;
}
```

### 3. Default Parameters

```php
// ✅ Good: Provide sensible defaults
function useCounter($initialValue = 0, $step = 1, $stateKey = "counter") {
    // Implementation
}

// ✅ Good: Make state key configurable for reusability
function useToggle($initialValue = false, $stateKey = "toggle") {
    // Implementation
}
```

### 4. Return Consistency

```php
// ✅ Good: Consistent return pattern
function useCounter($initialValue = 0) {
    $count = createState("counter", $initialValue);
    return $count; // Always return the state directly
}

// ✅ Also good: Always return an array/object
function useForm($initialData = []) {
    $formData = createState("form", $initialData);
    $errors = createState("form_errors", []);

    return [
        'data' => $formData,
        'errors' => $errors
    ];
}
```

## Hook Composition

You can use hooks within other hooks:

```php
function useCounterWithToggle($initialCount = 0) {
    $counter = useCounter($initialCount, 1, "composed_counter");
    $isVisible = useToggle(true, "counter_visibility");

    return [
        'count' => $counter,
        'isVisible' => $isVisible
    ];
}
```

## JavaScript Integration

### Updating Hook State from Frontend

```javascript
// Update simple state
phpspa.setState('counter', newValue)

// Update object state
phpspa.setState('form_data', {
	...currentFormData,
	fieldName: newValue,
})

// Update array state
phpspa.setState('todo_list', [...currentTodos, newTodo])
```

### Accessing Hook State in JavaScript

```javascript
// Get value from hook function
let value = await phpspa.__call('useCounter', 0)
```

## Common Hook Patterns

### Loading State Hook

```php
function useLoading($initialState = false, $stateKey = "loading") {
    $isLoading = createState($stateKey, $initialState);
    return $isLoading;
}
```

### API Data Hook

```php
function useApiData($endpoint, $stateKey = null) {
    $key = $stateKey ?? "api_" . md5($endpoint);
    $data = createState($key, null);
    $loading = createState($key . "_loading", false);
    $error = createState($key . "_error", null);

    return [
        'data' => $data,
        'loading' => $loading,
        'error' => $error
    ];
}
```

### Local Storage Sync Hook

```php
function useLocalStorage($key, $defaultValue = null, $stateKey = null) {
    $stateKey = $stateKey ?? "localStorage_" . $key;
    $value = createState($stateKey, $defaultValue);

    return $value;
}
```

## Troubleshooting

### Common Issues

1. **State not updating**: Ensure unique state keys across different hook instances
2. **JSON conversion issues**: Remember that state variables auto-convert to JSON in string contexts
3. **Hook conflicts**: Use descriptive and unique state keys

### Debugging Tips

```php
function useDebugCounter($initialValue = 0) {
    $count = createState("debug_counter", $initialValue);

    // Debug: Log current state
    error_log("Counter state: " . $count);

    return $count;
}
```

## Migration from Class Components

If you're converting from class-based components to hooks:

```php
// Before: Class-based approach
class CounterComponent {
    private $count;

    public function __construct() {
        $this->count = createState("counter", 0);
    }

    public function render() {
        return "<div>Count: {$this->count}</div>";
    }
}

// After: Hook-based approach
function useCounter($initialValue = 0) {
    return createState("counter", $initialValue);
}

function CounterComponent() {
    $counter = useCounter(0);
    return "<div>Count: {$counter}</div>";
}
```

---

!!! success "Next Steps"
Now that you understand phpSPA hooks, try creating your own custom hooks for common patterns in your application. Remember to keep them focused, reusable, and well-named!

!!! info "Related Documentation" - [State Management](./17-state-management.md) - Core state concepts
