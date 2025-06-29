# phpSPA v1.1.2

A lightweight PHP Single Page Application framework for building dynamic web applications

---

## What's New in v1.1.2

### 🚀 Key Features Added

-  **Optional Routing**: Components can now render without explicit routes
-  **Auto-Reload Components**: Built-in component refresh functionality
-  **Direct PHP Function Calls**: Call PHP functions directly from JavaScript
-  **CORS Support**: Comprehensive Cross-Origin Resource Sharing configuration

---

## Installation

```bash
composer require phpspa/phpspa
```

## Quick Start

### Basic Application Setup

```php
<?php
require_once 'vendor/autoload.php';

use phpSPA\App;

$app = new App(require 'Layout.php');
$app->run();
```

---

## Core Features

### 1. Optional Component Routing

!!! info "New in v1.1.2"
The `route()` method is now optional. Components will render automatically if the request method matches the component method, even without explicit routes.

```php
<?php
use phpSPA\Component;

// This component will render without explicit routing
return new Component(function () {
    return "<h1>Welcome to phpSPA!</h1>";
});
```

### 2. Auto-Reload Components

!!! tip "Real-time Updates"
Use the `reload()` method to automatically refresh components at specified intervals.

```php
<?php
use phpSPA\Component;

return (new Component(function () {
    $time = date("h:i:s");
    return "Current Time: $time";
}))
    ->route('/timer')
    ->title('Live Timer')
    ->reload(1000); // Reload every 1000ms (1 second)
```

**Method Signature:**

```php
reload(int $milliseconds = 0)
```

### 3. Direct PHP Function Calls

!!! success "JavaScript ↔ PHP Integration"
Call PHP functions directly from JavaScript using `phpspa.__call()`.

**PHP Side:**

```php
<?php
use phpSPA\Component;
use function phpSPA\Component\createState;

function greetUser($name) {
    return [
        'message' => "Hello, $name!",
        'timestamp' => time()
    ];
}

return (new Component(function () {
    $counter = createState('counter', 0);

    return <<<HTML
        <button id="greet-btn">Greet User</button>
        <div id="result"></div>
    HTML;
}))
    ->route('/greeting')
    ->script(fn() => <<<JS
        document.getElementById('greet-btn').addEventListener('click', async () => {
            const response = await phpspa.__call('greetUser', 'World');
            document.getElementById('result').innerHTML = response.message;
        });
    JS);
```

**JavaScript Usage:**

```javascript
// Call PHP function with parameters
const result = await phpspa.__call('functionName', param1, param2, ...);
```

### 4. CORS Configuration

!!! warning "Cross-Origin Requests"
Configure CORS settings to handle cross-origin requests properly.

```php
<?php
use phpSPA\App;

$app = new App(require 'Layout.php');

$app->cors([
    // Allow specific domains or use '*' for all
    'allow_origin' => '*',

    // Allowed HTTP methods
    'allow_methods' => [
        'GET', 'POST', 'PUT', 'PATCH',
        'DELETE', 'OPTIONS', 'PHPSPA_GET'
    ],

    // Allowed headers
    'allow_headers' => [
        'Content-Type', 'Authorization',
        'Origin', 'Accept', 'X-CSRF-Token'
    ],

    // Exposed headers
    'expose_headers' => [
        'Content-Length', 'Content-Range', 'X-Custom-Header'
    ],

    // Cache preflight requests (seconds)
    'max_age' => 3600,

    // Allow credentials
    'allow_credentials' => true,
    'supports_credentials' => true,
]);

$app->run();
```

---

## Component Structure

### Basic Component

```php
<?php
use phpSPA\Component;

return (new Component(function () {
    return <<<HTML
        <div class="component">
            <h2>My Component</h2>
            <p>Component content goes here</p>
        </div>
    HTML;
}))
    ->route('/my-component')
    ->title('My Component Page');
```

### Stateful Component

```php
<?php
use phpSPA\Component;
use function phpSPA\Component\createState;

return (new Component(function () {
    $counter = createState('counter', 0);

    return <<<HTML
        <div>
            <h2>Counter: {$counter()}</h2>
            <button onclick="increment()">+</button>
        </div>
    HTML;
}))
    ->route('/counter')
    ->script(fn() => <<<JS
        function increment() {
            // Component logic here
        }
    JS);
```

---

## Advanced Examples

### Real-time Data Dashboard

```php
<?php
use phpSPA\Component;

function fetchSystemStats() {
    return [
        'cpu_usage' => rand(10, 90),
        'memory_usage' => rand(30, 80),
        'disk_space' => rand(20, 95)
    ];
}

return (new Component(function () {
    $stats = fetchSystemStats();

    return <<<HTML
        <div class="dashboard">
            <h2>System Monitor</h2>
            <div class="stats">
                <div>CPU: {$stats['cpu_usage']}%</div>
                <div>Memory: {$stats['memory_usage']}%</div>
                <div>Disk: {$stats['disk_space']}%</div>
            </div>
        </div>
    HTML;
}))
    ->route('/dashboard')
    ->title('System Dashboard')
    ->reload(5000); // Update every 5 seconds
```

### Interactive Form with PHP Validation

```php
<?php
use phpSPA\Component;

function validateEmail($email) {
    return [
        'valid' => filter_var($email, FILTER_VALIDATE_EMAIL),
        'message' => filter_var($email, FILTER_VALIDATE_EMAIL)
            ? 'Email is valid'
            : 'Please enter a valid email'
    ];
}

return (new Component(function () {
    return <<<HTML
        <form id="email-form">
            <input type="email" id="email" placeholder="Enter your email">
            <button type="submit">Validate</button>
            <div id="validation-result"></div>
        </form>
    HTML;
}))
    ->route(['/email-validator'])
    ->script(fn() => <<<JS
        document.getElementById('email-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const result = await phpspa.__call('validateEmail', email);

            document.getElementById('validation-result').innerHTML =
                `<p style="color: ${result.valid ? 'green' : 'red'}">${result.message}</p>`;
        });
    JS);
```

---

## Method Reference

### Component Methods

| Method     | Description                        | Parameters          |
| ---------- | ---------------------------------- | ------------------- |
| `route()`  | Define component routes (optional) | `array $routes`     |
| `title()`  | Set page title                     | `string $title`     |
| `reload()` | Auto-reload interval               | `int $milliseconds` |
| `script()` | Add JavaScript code                | `callable $script`  |

### App Methods

| Method   | Description             | Parameters      |
| -------- | ----------------------- | --------------- |
| `cors()` | Configure CORS settings | `array $config` |
| `run()`  | Start the application   | None            |

### JavaScript Functions

| Function          | Description        | Usage                                      |
| ----------------- | ------------------ | ------------------------------------------ |
| `phpspa.__call()` | Call PHP functions | `await phpspa.__call('funcName', ...args)` |

---

## Best Practices

!!! tip "Performance Tips" - Use appropriate reload intervals to balance real-time updates with server load - Implement proper error handling in both PHP and JavaScript - Validate data on both client and server sides

!!! note "Security Considerations" - Always validate and sanitize data from client-side calls - Configure CORS settings appropriately for your deployment environment - Use HTTPS in production environments

---

## Browser Support

phpSPA works with all modern browsers that support:

-  ES6/ES2015 features
-  Fetch API
-  Async/Await

---

## Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/dconco/phpspa) for details.

## License

phpSPA is open-source software licensed under the [MIT License](../LICENSE).

---

## Changelog

### v1.1.2

-  ✨ Made `route()` method optional in component definition
-  ✨ Added `reload(int $milliseconds = 0)` method for auto-refreshing components
-  ✨ Added `phpspa.__call()` JavaScript function for direct PHP function calls
-  ✨ Added `cors()` method to App class for CORS configuration
