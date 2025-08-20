# 🔒 Security

phpSPA provides essential security features to protect your applications from common web vulnerabilities. This guide covers the built-in security mechanisms and best practices for secure component development.

!!! warning "Security Foundation"
    Security is **never optional**. phpSPA includes CSRF protection, input validation, and session management to help you build secure applications from the start.

---

## 🛡️ CSRF Protection

Cross-Site Request Forgery (CSRF) protection is built into phpSPA's component system. Use the `<Component.Csrf />` component to protect forms and state-changing operations.

### Basic CSRF Usage

```php
function ContactForm() {
    return <<<HTML
        <form method="POST" action="/contact">
            <Component.Csrf name="contact-form" />
            
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="email" name="email" placeholder="Your Email" required>
            <textarea name="message" placeholder="Your Message" required></textarea>
            
            <button type="submit">Send Message</button>
        </form>
    HTML;
}
```

### CSRF Validation

```php
use Component\Csrf;

function ContactFormHandler() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Initialize CSRF validator with form name
        $csrf = new Csrf("contact-form");
        
        // Validate CSRF token
        if (!$csrf->verify()) {
            http_response_code(403);
            return <<<HTML
                <div class="error">
                    <h2>⚠️ Security Error</h2>
                    <p>Invalid CSRF token. Please refresh and try again.</p>
                </div>
            HTML;
        }
        
        // Process form safely
        $name = htmlspecialchars($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $message = htmlspecialchars($_POST['message']);
        
        if (!$email) {
            return "<div class='error'>Invalid email address</div>";
        }
        
        // Safe to process the form
        return <<<HTML
            <div class="success">
                <h2>✅ Message Sent</h2>
                <p>Thank you, {$name}! We'll get back to you soon.</p>
            </div>
        HTML;
    }
}
```

### Advanced CSRF Configuration

```php
use Component\Csrf;

function SecureForm() {
    // Create CSRF with custom configuration
    $csrf = new Csrf("secure-form", [
        'expire_time' => 3600,      // 1 hour expiration
        'expire_after_use' => true, // Single use tokens
        'max_tokens' => 5           // Maximum stored tokens
    ]);
    
    return <<<HTML
        <form method="POST" onsubmit="return validateAndSubmit(event)">
            <Component.Csrf name="secure-form" />
            
            <input type="password" name="current_password" required>
            <input type="password" name="new_password" required>
            
            <button type="submit">Change Password</button>
        </form>
        
        <script data-type="phpspa/script">
            function validateAndSubmit(event) {
                const currentPassword = event.target.current_password.value;
                const newPassword = event.target.new_password.value;
                
                // Client-side validation
                if (newPassword.length < 8) {
                    alert('Password must be at least 8 characters');
                    return false;
                }
                
                return true; // Allow form submission
            }
        </script>
    HTML;
}
```

### CSRF Features

| Feature                     | Description                               | Benefit                 |
| --------------------------- | ----------------------------------------- | ----------------------- |
| **Automatic Tokens**        | Cryptographically secure token generation | Prevents CSRF attacks   |
| **Token Rotation**          | Automatic token renewal after use         | Enhanced security       |
| **Timing-Safe Validation**  | Uses `hash_equals()` for comparison       | Prevents timing attacks |
| **Configurable Expiration** | Customizable token lifetime               | Balance security vs UX  |
| **Multiple Named Tokens**   | Support for multiple forms per page       | Flexible implementation |

---

## 🔒 Request Handling & Input Validation

phpSPA's `Request` class provides secure methods for handling HTTP requests and validating input data.

### Basic Request Handling

```php
use phpSPA\Http\Request;

function UserRegistration() {
    $request = new Request();
    
    if ($request->method() === 'POST') {
        // Get POST data
        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');
        
        // Validation
        $errors = [];
        
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address";
        }
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        if (!empty($errors)) {
            $errorList = implode('<br>', $errors);
            return <<<HTML
                <div class="error-messages">
                    <h3>⚠️ Validation Errors</h3>
                    {$errorList}
                </div>
            HTML;
        }
        
        // Process registration safely
        return processRegistration($username, $email, $password);
    }
    
    return RegistrationForm();
}

function RegistrationForm() {
    return <<<HTML
        <form method="POST">
            <Component.Csrf name="registration" />
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required minlength="3">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required minlength="8">
            </div>
            
            <button type="submit">Register</button>
        </form>
    HTML;
}
```

### Request Class Methods

```php
use phpSPA\Http\Request;

function RequestExample() {
    $request = new Request();
    
    // Get HTTP method
    $method = $request->method(); // 'GET', 'POST', etc.
    
    // Get all POST data
    $allPost = $request->post();
    
    // Get specific POST parameter
    $username = $request->post('username');
    
    // Get all GET parameters
    $allGet = $request->get();
    
    // Get specific GET parameter
    $page = $request->get('page');
    
    // Get client IP
    $clientIp = $request->ip();
    
    // Get file uploads
    $uploadedFile = $request->file('avatar');
    
    // Get session data
    $userId = $request->session('user_id');
    
    // Get headers
    $userAgent = $request->header('User-Agent');
    
    return <<<HTML
        <div class="request-info">
            <h2>Request Information</h2>
            <p>Method: {$method}</p>
            <p>Client IP: {$clientIp}</p>
            <p>User Agent: {htmlspecialchars($userAgent ?: 'Not available')}</p>
        </div>
    HTML;
}
```

### Input Sanitization

```php
function SecureContactForm() {
    $request = new Request();
    
    if ($request->method() === 'POST') {
        // Sanitize inputs
        $name = htmlspecialchars(trim($request->post('name')));
        $email = filter_var($request->post('email'), FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars(trim($request->post('message')));
        
        // Validate sanitized data
        if (empty($name)) {
            return "<div class='error'>Name is required</div>";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "<div class='error'>Valid email is required</div>";
        }
        
        if (empty($message)) {
            return "<div class='error'>Message is required</div>";
        }
        
        // Process safely
        return <<<HTML
            <div class="success">
                <h2>✅ Message Received</h2>
                <p>Thank you, {$name}! We'll respond to {$email} soon.</p>
            </div>
        HTML;
    }
    
    return ContactFormHtml();
}
```

---

## 🔐 Session Management

phpSPA's `Session` class provides secure session handling with built-in protections.

### Basic Session Usage

```php
use phpSPA\Http\Session;

function LoginHandler() {
    $request = new Request();
    
    if ($request->method() === 'POST') {
        $username = $request->post('username');
        $password = $request->post('password');
        
        // Validate credentials (implement your logic)
        if (validateCredentials($username, $password)) {
            // Start session
            Session::start();
            
            // Regenerate session ID to prevent fixation
            Session::regenerateId();
            
            // Store user data
            Session::set('user_id', getUserId($username));
            Session::set('username', $username);
            Session::set('login_time', time());
            
            return <<<HTML
                <div class="success">
                    <h2>✅ Login Successful</h2>
                    <p>Welcome back, {$username}!</p>
                </div>
            HTML;
        } else {
            return <<<HTML
                <div class="error">
                    <h2>❌ Login Failed</h2>
                    <p>Invalid username or password</p>
                </div>
            HTML;
        }
    }
    
    return LoginForm();
}

function LogoutHandler() {
    // Destroy session completely
    Session::destroy();
    
    return <<<HTML
        <div class="success">
            <h2>👋 Logged Out</h2>
            <p>You have been logged out successfully</p>
            <Component.Link to="/login" label="Login Again" />
        </div>
    HTML;
}
```

### Session Protection

```php
use phpSPA\Http\Session;

function ProtectedComponent() {
    // Check if user is logged in
    if (!Session::isActive() || !Session::has('user_id')) {
        return <<<HTML
            <div class="login-required">
                <h2>🔐 Login Required</h2>
                <p>Please log in to access this content</p>
                <Component.Link to="/login" label="Login" />
            </div>
        HTML;
    }
    
    $username = Session::get('username');
    $loginTime = Session::get('login_time');
    $loginDate = date('Y-m-d H:i', $loginTime);
    
    return <<<HTML
        <div class="protected-content">
            <h2>🔒 Protected Area</h2>
            <p>Welcome, {$username}!</p>
            <p>Logged in since: {$loginDate}</p>
            
            <button onclick="logout()">Logout</button>
        </div>
        
        <script data-type="phpspa/script">
            function logout() {
                fetch('/logout', { method: 'POST' })
                    .then(() => {
                        phpspa.navigate('/login');
                    });
            }
        </script>
    HTML;
}
```

### Session Class Methods

| Method                         | Description                 | Usage                          |
| ------------------------------ | --------------------------- | ------------------------------ |
| `Session::start()`             | Start session if not active | `Session::start()`             |
| `Session::isActive()`          | Check if session is active  | `Session::isActive()`          |
| `Session::get($key, $default)` | Get session value           | `Session::get('user_id')`      |
| `Session::set($key, $value)`   | Set session value           | `Session::set('user_id', 123)` |
| `Session::has($key)`           | Check if key exists         | `Session::has('user_id')`      |
| `Session::remove($key)`        | Remove session key          | `Session::remove('temp_data')` |
| `Session::regenerateId()`      | Regenerate session ID       | `Session::regenerateId()`      |
| `Session::destroy()`           | Destroy session             | `Session::destroy()`           |

---

## 🔒 SQL Injection Prevention

Always use prepared statements for database operations:

### Safe Database Operations

```php
class SecureDatabase {
    private $pdo;
    
    public function __construct($dsn, $username, $password) {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    
    public function findUser($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function createUser($username, $email, $passwordHash) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$username, $email, $passwordHash]);
    }
}
```

### Secure Component with Database

```php
function UserSearch() {
    $request = new Request();
    $searchTerm = $request->get('search');
    
    if (!empty($searchTerm) && strlen($searchTerm) >= 3) {
        $db = new SecureDatabase($dsn, $dbUser, $dbPass);
        
        // Safe search with prepared statement
        $stmt = $db->prepare("SELECT username, email FROM users WHERE username LIKE ? LIMIT 10");
        $stmt->execute(['%' . $searchTerm . '%']);
        $results = $stmt->fetchAll();
        
        $resultItems = array_map(function($user) {
            $username = htmlspecialchars($user['username']);
            $email = htmlspecialchars($user['email']);
            
            return <<<HTML
                <div class="user-result">
                    <strong>{$username}</strong>
                    <span>{$email}</span>
                </div>
            HTML;
        }, $results);
        
        $resultHtml = implode('', $resultItems);
    } else {
        $resultHtml = '<p>Enter at least 3 characters to search</p>';
    }
    
    return <<<HTML
        <div class="user-search">
            <h2>🔍 User Search</h2>
            
            <form method="GET">
                <input 
                    type="text" 
                    name="search"
                    placeholder="Search users..." 
                    value="{htmlspecialchars($searchTerm ?: '')}"
                    minlength="3"
                >
                <button type="submit">Search</button>
            </form>
            
            <div class="search-results">
                {$resultHtml}
            </div>
        </div>
    HTML;
}
```

---

## 📚 Security Best Practices

!!! tip "Development Practices"
    
    1. **Validate All Input**: Never trust user input, always validate and sanitize
    2. **Use Prepared Statements**: Prevent SQL injection with parameterized queries
    3. **Enable CSRF Protection**: Use `<Component.Csrf />` on all forms
    4. **Sanitize Output**: Use `htmlspecialchars()` for displaying user data
    5. **Secure Sessions**: Use `Session::regenerateId()` after login

!!! info "Production Security"
    
    1. **Use HTTPS**: Always encrypt data in transit
    2. **Set Security Headers**: Implement proper HTTP security headers
    3. **Regular Updates**: Keep PHP and dependencies updated
    4. **Error Handling**: Don't expose sensitive information in errors
    5. **Access Controls**: Implement proper authentication and authorization

!!! success "Component Security"
    
    1. **CSRF on Forms**: Every form should include CSRF protection
    2. **Input Validation**: Validate data before processing
    3. **Output Encoding**: Escape data before displaying
    4. **Session Management**: Use phpSPA's Session class properly
    5. **Database Safety**: Always use prepared statements

---

## 🚀 Next Steps

Explore related security topics:

<div class="buttons" markdown>
[Request Handling](../request-response/){ .md-button .md-button--primary }
[State Management](../state/){ .md-button }
[Component Guide](../components/){ .md-button }
[Best Practices](../best-practices/){ .md-button }
</div>

---

## 💡 Security Principles

**Security is built into phpSPA's core.** The framework provides:

- **🛡️ Built-in CSRF Protection**: Automatic token generation and validation
- **🔒 Secure Request Handling**: Input validation and sanitization helpers
- **⚡ Session Security**: Secure session management with fixation prevention
- **🧩 Component Safety**: Security-focused component development patterns
- **📊 Validation Tools**: Built-in validation and sanitization methods

Remember: **Security is about consistent application of good practices, not complex frameworks!**
