# PHP-JavaScript Integration

## Overview

phpSPA v1.1.5 introduces enhanced PHP-JavaScript integration with the new `useFunction()` utility and improved `__call()` alias. This provides secure, direct communication between PHP backend functions and JavaScript frontend code.

## Key Features

- **Direct Function Calls**: Call PHP functions from JavaScript seamlessly
- **Enhanced Security**: 10x more secure `__call()` implementation
- **Token-Based Authentication**: Secure function access control
- **Namespace Support**: Full namespace compatibility
- **Async Support**: Promise-based JavaScript integration

## The useFunction() Utility

### Basic Usage

```php
<?php
use function Component\useFunction;

// Define your PHP function
function Login($args) {
    return "<h2>Login Component</h2>";
}

// In your component
function LoginComponent() {
    $loginApi = useFunction('Login');
    
    return <<<HTML
    <button id="login-btn">Login</button>
    <script>
        document.getElementById('login-btn').onclick = async () => {
            const response = await {$loginApi('user-data')};
            console.log(response);
        }
    </script>
    HTML;
}
```

### With Namespaces

```php
<?php
namespace MyApp\Auth;

function authenticate($credentials) {
    return ['status' => 'success', 'token' => 'abc123'];
}

// In your component
use function Component\useFunction;

function AuthComponent() {
    $authApi = useFunction('\\MyApp\\Auth\\authenticate');
    
    return <<<HTML
    <form id="auth-form">
        <input type="text" name="username" />
        <input type="password" name="password" />
        <button type="submit">Login</button>
    </form>
    <script>
        document.getElementById('auth-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const credentials = Object.fromEntries(formData);
            
            const result = await {$authApi(credentials)};
            if (result.status === 'success') {
                localStorage.setItem('token', result.token);
                window.location.href = '/dashboard';
            }
        }
    </script>
    HTML;
}
```

## Enhanced __call() Method

### Traditional Method

```php
<?php
function fetchUserData($userId) {
    return ['id' => $userId, 'name' => 'John Doe'];
}

function UserProfile() {
    $userApi = useFunction('fetchUserData');
    
    return <<<HTML
    <div id="user-profile"></div>
    <script>
        // Traditional __call method
        document.addEventListener('DOMContentLoaded', () => {
            __call("{$userApi->token}", 123).then(userData => {
                document.getElementById('user-profile').innerHTML = 
                    `<h3>${userData.name}</h3><p>ID: ${userData.id}</p>`;
            });
        });
    </script>
    HTML;
}
```

### Direct Method (Recommended)

```php
<?php
function UserProfile() {
    $userApi = useFunction('fetchUserData');
    
    return <<<HTML
    <div id="user-profile"></div>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const userData = await {$userApi(123)};
            document.getElementById('user-profile').innerHTML = 
                `<h3>${userData.name}</h3><p>ID: ${userData.id}</p>`;
        });
    </script>
    HTML;
}
```

## Security Features

### Token-Based Authentication

Each function call generates a secure token:

```php
<?php
$api = useFunction('myFunction');
echo $api->token; // Outputs: secure-random-token-string
```

### Automatic Token Validation

The system automatically validates tokens on each call:

```javascript
// This is handled automatically
__call("secure-token", "arguments"); // ✅ Valid
__call("invalid-token", "arguments"); // ❌ Rejected
```

### Namespace Protection

Functions are protected by namespace boundaries:

```php
<?php
// Protected function
namespace Admin\Core;
function deleteUser($id) { /* ... */ }

// Public function
function getUser($id) { /* ... */ }

// Only public functions can be called without proper namespace access
$publicApi = useFunction('getUser'); // ✅ Allowed
$adminApi = useFunction('\\Admin\\Core\\deleteUser'); // ✅ Allowed with full namespace
```

## Advanced Usage

### Error Handling

```php
<?php
function riskyOperation($data) {
    if (!$data) {
        throw new Exception('Invalid data provided');
    }
    return ['success' => true];
}

function ErrorHandlingComponent() {
    $api = useFunction('riskyOperation');
    
    return <<<HTML
    <button id="test-btn">Test Operation</button>
    <div id="result"></div>
    <script>
        document.getElementById('test-btn').onclick = async () => {
            try {
                const result = await {$api(null)};
                document.getElementById('result').textContent = 'Success!';
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }
    </script>
    HTML;
}
```

### Multiple Function Calls

```php
<?php
function getUser($id) { return ['id' => $id, 'name' => 'User ' . $id]; }
function getOrders($userId) { return ['orders' => [1, 2, 3]]; }

function DashboardComponent() {
    $userApi = useFunction('getUser');
    $ordersApi = useFunction('getOrders');
    
    return <<<HTML
    <div id="dashboard"></div>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const [user, orders] = await Promise.all([
                    {$userApi(1)},
                    {$ordersApi(1)}
                ]);
                
                document.getElementById('dashboard').innerHTML = `
                    <h2>Welcome ${user.name}</h2>
                    <p>You have ${orders.orders.length} orders</p>
                `;
            } catch (error) {
                console.error('Failed to load dashboard:', error);
            }
        });
    </script>
    HTML;
}
```

### Class Methods

```php
<?php
class UserService {
    public function getProfile($userId) {
        return ['id' => $userId, 'profile' => 'data'];
    }
    
    public static function getStats() {
        return ['total_users' => 1000];
    }
}

function UserComponent() {
    $service = new UserService();
    $profileApi = useFunction([$service, 'getProfile']);
    $statsApi = useFunction(['UserService', 'getStats']);
    
    return <<<HTML
    <div id="user-section"></div>
    <script>
        (async () => {
            const profile = await {$profileApi(123)};
            const stats = await {$statsApi()};
            
            document.getElementById('user-section').innerHTML = `
                <p>User: ${profile.id}</p>
                <p>Total Users: ${stats.total_users}</p>
            `;
        })();
    </script>
    HTML;
}
```

## Performance Optimization

### Function Caching

```php
<?php
$cache = [];

function expensiveOperation($data) {
    global $cache;
    $key = md5(serialize($data));
    
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    // Expensive computation
    $result = performComplexCalculation($data);
    $cache[$key] = $result;
    
    return $result;
}
```

### Batching Requests

```php
<?php
function processBatch($operations) {
    $results = [];
    foreach ($operations as $op) {
        $results[] = processOperation($op);
    }
    return $results;
}

function BatchComponent() {
    $batchApi = useFunction('processBatch');
    
    return <<<HTML
    <script>
        // Instead of multiple individual calls
        const operations = [
            {type: 'create', data: {...}},
            {type: 'update', data: {...}},
            {type: 'delete', data: {...}}
        ];
        
        const results = await {$batchApi($operations)};
    </script>
    HTML;
}
```

## Best Practices

### 1. Function Naming

```php
<?php
// Good: Descriptive function names
function getUserProfile($userId) { /* ... */ }
function updateUserSettings($userId, $settings) { /* ... */ }

// Avoid: Generic names
function process($data) { /* ... */ }
function handle($input) { /* ... */ }
```

### 2. Parameter Validation

```php
<?php
function updateProfile($userId, $data) {
    // Always validate input
    if (!is_numeric($userId) || $userId <= 0) {
        throw new InvalidArgumentException('Invalid user ID');
    }
    
    if (!is_array($data) || empty($data)) {
        throw new InvalidArgumentException('Invalid profile data');
    }
    
    // Process update
    return updateUserProfile($userId, $data);
}
```

### 3. Return Consistent Data Structures

```php
<?php
function apiResponse($success, $data = null, $error = null) {
    return [
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => time()
    ];
}

function createUser($userData) {
    try {
        $user = User::create($userData);
        return apiResponse(true, $user);
    } catch (Exception $e) {
        return apiResponse(false, null, $e->getMessage());
    }
}
```

### 4. Error Handling

```php
<?php
function safeOperation($data) {
    try {
        return performOperation($data);
    } catch (Exception $e) {
        // Log error
        error_log("Operation failed: " . $e->getMessage());
        
        // Return user-friendly error
        return ['error' => 'Operation failed. Please try again.'];
    }
}
```

## Migration Guide

### From v1.1.4 to v1.1.5

**Old Method:**
```javascript
// Manual __call usage
phpspa.__call('functionName', arguments).then(result => {
    // Handle result
});
```

**New Method:**
```php
<?php
$api = useFunction('functionName');
?>
<script>
    // Direct usage
    const result = await {<?= $api('arguments') ?>};
</script>
```

### Namespace Updates

**Before:**
```php
use phpSPA\Component\useFunction;
```

**After:**
```php
use function Component\useFunction;
```

## Debugging

### Enable Debug Mode

```php
<?php
// Enable function call debugging
define('PHPSPA_DEBUG_FUNCTIONS', true);

$api = useFunction('myFunction');
// Will output debug information to browser console
```

### Token Inspection

```php
<?php
$api = useFunction('myFunction');
echo "Token: " . $api->token; // For debugging only
echo "Function: " . $api->function; // Function name
```

## Security Considerations

### 1. Never Expose Sensitive Functions

```php
<?php
// DON'T expose these
function deleteAllUsers() { /* ... */ }
function getPassword($userId) { /* ... */ }
function executeSQL($query) { /* ... */ }

// DO expose these
function getUserProfile($userId) { /* ... */ }
function updateUserSettings($userId, $settings) { /* ... */ }
```

### 2. Validate All Input

```php
<?php
function updateUser($userId, $data) {
    // Sanitize input
    $userId = filter_var($userId, FILTER_VALIDATE_INT);
    $data = filter_var_array($data, [
        'name' => FILTER_SANITIZE_STRING,
        'email' => FILTER_VALIDATE_EMAIL
    ]);
    
    if (!$userId || !$data) {
        throw new InvalidArgumentException('Invalid input');
    }
    
    // Proceed with update
}
```

### 3. Use Authentication

```php
<?php
function protectedFunction($data) {
    if (!isUserAuthenticated()) {
        throw new UnauthorizedException('Access denied');
    }
    
    // Function logic
}
```

This enhanced PHP-JavaScript integration provides a powerful, secure way to create dynamic applications while maintaining clean separation between frontend and backend code.
