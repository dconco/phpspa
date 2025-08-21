# CSRF Protection

## Overview

phpSPA v1.1.5 introduces a comprehensive CSRF (Cross-Site Request Forgery) protection system with the new `<Component.Csrf />` component. This provides automatic token generation, validation, and management to secure your applications against CSRF attacks.

## Key Features

-  **Multiple Named Tokens**: Support for different forms with unique tokens
-  **Automatic Cleanup**: Old tokens are automatically removed
-  **Built-in Expiration**: Tokens expire after 1 hour by default
-  **Timing-Safe Validation**: Prevents timing attacks
-  **Token Rotation**: Automatic token regeneration
-  **Reuse Prevention**: Tokens can be configured to expire after use

## Quick Start

### Basic Form Protection

```php
<?php
function ContactForm() {
    return <<<HTML
    <form method="POST" action="/contact">
        <Component.Csrf name="contact-form" />
        <input type="text" name="name" required>
        <input type="email" name="email" required>
        <textarea name="message" required></textarea>
        <button type="submit">Send Message</button>
    </form>
    HTML;
}
```

### Token Verification

```php
<?php
use Component\Csrf;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = new Csrf("contact-form");

    if (!$csrf->verify()) {
        die('Invalid CSRF token!');
    }

    // Process form data safely
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Handle contact form submission
    processContactForm($name, $email, $message);
}
```

## CSRF Component Usage

### Basic Component

```php
<?php
// Simple CSRF token for a form
echo '<Component.Csrf name="user-form" />';
```

This generates HTML like:

```html
<input type="hidden" name="user-form" value="abc123def456..." />
```

### Multiple Forms on Same Page

```php
<?php
function LoginAndRegisterPage() {
    return <<<HTML
    <div class="auth-container">
        <form id="login-form" method="POST" action="/login">
            <Component.Csrf name="login-form" />
            <input type="text" name="username" required>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>

        <form id="register-form" method="POST" action="/register">
            <Component.Csrf name="register-form" />
            <input type="text" name="username" required>
            <input type="email" name="email" required>
            <input type="password" name="password" required>
            <button type="submit">Register</button>
        </form>
    </div>
    HTML;
}
```

## CSRF Class API

### Creating CSRF Instance

```php
<?php
use Component\Csrf;

// Create CSRF instance for specific form
$csrf = new Csrf("form-name");
```

### Token Generation

```php
<?php
// Generate new token
$token = $csrf->generate();
echo "Token: " . $token;

// Token is automatically stored in session (avoid generating multiple tokens)
```

### Token Verification

```php
<?php
// Verify token (default: expire after use)
if ($csrf->verify()) {
    echo "Token is valid!";
} else {
    echo "Invalid or expired token!";
}

// Verify token but don't expire it
if ($csrf->verify(false)) {
    echo "Token is valid and can be reused!";
}
```

### Manual Verification

```php
<?php
// Verify token (default: expire after use)
if ($csrf->verifyToken($savedToken)) {
    echo "Token is valid!";
} else {
    echo "Invalid or expired token!";
}

// Verify token but don't expire it
if ($csrf->verifyToken($savedToken, false)) {
    echo "Token is valid and can be reused!";
}
```

### Token Management

```php
<?php
// Get current token without generating new one and if it doesn't exist, it generates new one
$currentToken = $csrf->getToken();
```

## Advanced Usage

### Custom Token Expiration

```php
<?php
class CustomCsrf extends Csrf {
    protected $tokenLifetime = 3600; // 1 hour (default)
    protected $maxTokens = 10;       // Max tokens per form name

    // Custom expiration time (in seconds)
    public function setTokenLifetime($seconds) {
        $this->tokenLifetime = $seconds;
        return $this;
    }
}

// Usage
$csrf = (new CustomCsrf("long-form"))
    ->setTokenLifetime(7200); // 2 hours

if (!$csrf->verify()) {
    die('Token expired or invalid');
}
```

### AJAX Form Protection

```php
<?php
function AjaxForm() {
    $csrf = new Csrf("ajax-form");
    $token = $csrf->generate();

    return <<<HTML
    <form id="ajax-form">
        <input type="text" name="data" required>
        <button type="submit">Submit</button>
    </form>

    <script>
        document.getElementById('ajax-form').onsubmit = async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('ajax-form', '{$token}');

            try {
                const response = await fetch('/api/submit', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    console.log('Form submitted successfully');
                } else {
                    console.error('Form submission failed');
                }
            } catch (error) {
                console.error('Network error:', error);
            }
        };
    </script>
    HTML;
}
```

### API Endpoint Protection

```php
<?php
use phpSPA\Http\Request;

$request = new Request();

// API endpoint with CSRF protection
function handleApiRequest() {
    if ($request->method() !== 'POST') {
        http_response_code(405);
        return json_encode(['error' => 'Method not allowed']);
    }

    $csrf = new Csrf('ajax-form);
    $token = $request->post('ajax-form') ?? '';

    if (!$csrf->verifyToken($token)) {
        http_response_code(403);
        return json_encode(['error' => 'Invalid CSRF token']);
    }

    // Process API request
    $data = processApiData($_POST);

    return json_encode(['success' => true, 'data' => $data]);
}
```

## Security Features

### Cryptographically Secure Tokens

```php
<?php
// Tokens are generated using random_bytes()
$token = bin2hex(random_bytes(32)); // 64-character hex string
```

### Timing-Safe Validation

```php
<?php
// Uses hash_equals() to prevent timing attacks
public function verify($expireAfterUse = true) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $storedToken = $this->getStoredToken();

    if (!$storedToken || !hash_equals($storedToken, $submittedToken)) {
        return false;
    }

    if ($expireAfterUse) {
        $this->expireToken();
    }

    return true;
}
```

### Automatic Token Rotation

```php
<?php
function RotatingTokenForm() {
    return <<<HTML
    <form method="POST" action="/submit">
        <Component.Csrf name="rotating-form" />
        <input type="text" name="data">
        <button type="submit">Submit</button>
    </form>

    <script>
        // Auto-refresh token every 30 minutes
        setInterval(() => {
            fetch('/refresh-csrf-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    form_name: 'rotating-form'
                })
            })
            .then(response => response.json())
            .then(data => {
                const tokenInput = document.querySelector('input[name="csrf_token"]');
                if (tokenInput && data.token) {
                    tokenInput.value = data.token;
                }
            });
        }, 30 * 60 * 1000); // 30 minutes
    </script>
    HTML;
}
```

## Integration with phpSPA Components

### With Form Components

```php
<?php
namespace Components\Forms;

class SecureForm {
    private $formName;
    private $csrf;

    public function __construct($formName) {
        $this->formName = $formName;
        $this->csrf = new \Component\Csrf($formName);
    }

    public function __render($props) {
        $action = $props['action'] ?? '/submit';
        $method = $props['method'] ?? 'POST';

        return <<<HTML
        <form action="{$action}" method="{$method}" class="secure-form">
            <Component.Csrf name="{$this->formName}" />
            {$this->renderFields($props['fields'] ?? [])}
            <button type="submit">{$props['submit_text'] ?? 'Submit'}</button>
        </form>
        HTML;
    }

    private function renderFields($fields) {
        $html = '';
        foreach ($fields as $field) {
            $html .= $this->renderField($field);
        }
        return $html;
    }

    private function renderField($field) {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? ucfirst($name);
        $required = $field['required'] ?? false;

        $requiredAttr = $required ? 'required' : '';

        return <<<HTML
        <div class="form-group">
            <label for="{$name}">{$label}</label>
            <input type="{$type}" name="{$name}" id="{$name}" {$requiredAttr}>
        </div>
        HTML;
    }

    public function verify() {
        return $this->csrf->verify();
    }
}

// Usage
$contactForm = new SecureForm('contact-form');

if ($request->method() === 'POST') {
    if ($contactForm->verify()) {
        // Process form
    } else {
        // Handle CSRF error
    }
}

echo $contactForm->render([
    'action' => '/contact',
    'fields' => [
        ['name' => 'name', 'label' => 'Your Name', 'required' => true],
        ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
        ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true]
    ],
    'submit_text' => 'Send Message'
]);
```

### With State Management

```php
<?php
function StatefulForm() {
    $csrf = new \Component\Csrf('stateful-form');
    $formData = createState('formData', []);

    return <<<HTML
    <form id="stateful-form">
        <Component.Csrf name="stateful-form" />
        <input type="text" name="title" placeholder="Title"
               onchange="phpspa.setState('formData', {...formData, title: this.value})">
        <textarea name="content" placeholder="Content"
                  onchange="phpspa.setState('formData', {...formData, content: this.value})"></textarea>
        <button type="submit">Save</button>
    </form>

    <script>
        document.getElementById('stateful-form').onsubmit = async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const response = await fetch('/save', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                phpspa.setState('formData', {});
                e.target.reset();
                alert('Saved successfully!');
            }
        };
    </script>
    HTML;
}
```

## Best Practices

### 1. Unique Form Names

```php
<?php
// Good: Descriptive, unique names
new Csrf('user-profile-form');
new Csrf('password-change-form');
new Csrf('article-comment-form');

// Avoid: Generic names
new Csrf('form1');
new Csrf('data');
```

### 2. Server-Side Validation

```php
<?php
function processForm($formName) {
    $csrf = new Csrf($formName);

    // Always verify CSRF first
    if (!$csrf->verify()) {
        http_response_code(403);
        return ['error' => 'Security validation failed'];
    }

    // Then validate form data
    $errors = validateFormData($_POST);
    if (!empty($errors)) {
        return ['errors' => $errors];
    }

    // Process valid, secure form
    return processValidForm($_POST);
}
```

### 3. Error Handling

```php
<?php
function handleFormSubmission() {
    try {
        $csrf = new Csrf($_POST['csrf_name'] ?? '');

        if (!$csrf->verify()) {
            throw new SecurityException('CSRF validation failed');
        }

        // Process form
        return processForm($_POST);

    } catch (SecurityException $e) {
        error_log('Security violation: ' . $e->getMessage());
        http_response_code(403);
        return ['error' => 'Security validation failed'];

    } catch (Exception $e) {
        error_log('Form processing error: ' . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Internal server error'];
    }
}
```

### 4. Token Refresh for Long Forms

```php
<?php
function LongForm() {
    return <<<HTML
    <form id="long-form" method="POST" action="/submit">
        <Component.Csrf name="long-form" />

        <!-- Many form fields -->

        <button type="submit">Submit</button>
    </form>

    <script>
        // Refresh token every 45 minutes (before 1-hour expiry)
        setInterval(async () => {
            try {
                const response = await fetch('/refresh-token', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({form_name: 'long-form'})
                });

                const data = await response.json();
                const tokenInput = document.querySelector('input[name="csrf_token"]');

                if (tokenInput && data.token) {
                    tokenInput.value = data.token;
                }
            } catch (error) {
                console.warn('Failed to refresh CSRF token:', error);
            }
        }, 45 * 60 * 1000);
    </script>
    HTML;
}
```

## Configuration

### Custom Configuration

```php
<?php
// config/csrf.php
return [
    'token_lifetime' => 3600, // 1 hour
    'max_tokens' => 10,       // Max tokens per form
    'expire_after_use' => true,
    'session_key' => '_csrf_tokens'
];
```

### Environment-Based Settings

```php
<?php
class CsrfConfig {
    public static function getConfig() {
        $baseConfig = [
            'token_lifetime' => 3600,
            'max_tokens' => 10,
            'expire_after_use' => true
        ];

        if ($_ENV['APP_ENV'] === 'development') {
            $baseConfig['token_lifetime'] = 86400; // 24 hours for development
        }

        return $baseConfig;
    }
}
```

The CSRF protection system provides robust security against cross-site request forgery attacks while maintaining ease of use and integration with phpSPA's component system.
