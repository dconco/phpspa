# 🛡️ Security - Production-Ready Protection

Security is **built into phpSPA by design**. From CSRF protection to input validation, phpSPA provides enterprise-grade security features that work out of the box, keeping your applications safe without compromising developer experience.

!!! success "Security-First Philosophy"
    
    **"Secure by default, flexible by choice"** — phpSPA implements security best practices automatically, while giving you the tools to customize protection for your specific needs.

---

## 🎯 Security Overview

phpSPA addresses the most common web application vulnerabilities:

<div class="grid cards" markdown>

-   **🛡️ CSRF Protection**
    
    ---
    
    Built-in Cross-Site Request Forgery protection with automatic token management and validation.

-   **🔒 Input Validation**
    
    ---
    
    Comprehensive request handling with automatic sanitization and type validation.

-   **⚡ XSS Prevention**
    
    ---
    
    Automatic output escaping and Content Security Policy headers for XSS mitigation.

-   **🔐 Secure Headers**
    
    ---
    
    Automatic security headers including HSTS, X-Frame-Options, and Content-Type protection.

</div>

---

## 🛡️ CSRF Protection

Cross-Site Request Forgery (CSRF) protection is **automatically enabled** in phpSPA with the `<Component.Csrf />` component.

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

| Feature | Description | Benefit |
|---------|-------------|---------|
| **Automatic Tokens** | Cryptographically secure token generation | Prevents CSRF attacks |
| **Token Rotation** | Automatic token renewal after use | Enhanced security |
| **Timing-Safe Validation** | Uses `hash_equals()` for comparison | Prevents timing attacks |
| **Configurable Expiration** | Customizable token lifetime | Balance security vs UX |
| **Multiple Named Tokens** | Support for multiple forms per page | Flexible implementation |

---

## 🔒 Input Validation & Sanitization

phpSPA provides comprehensive input handling through the `Request` class and built-in validation.

### Request Handling

```php
use phpSPA\Http\Request;

function UserRegistration(Request $request) {
    if ($request->isPost()) {
        // Get and validate inputs
        $username = $request->input('username');
        $email = $request->input('email');
        $password = $request->input('password');
        
        // Validation rules
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
            $errorListHtml = implode('<br>', $errors);
            return <<<HTML
                <div class="error-messages">
                    <h3>⚠️ Validation Errors</h3>
                    {$errorListHtml}
                </div>
            HTML;
        }
        
        // Safe to process registration
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

### Input Sanitization Helpers

```php
class SecurityHelper {
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $allowHtml = false) {
        if ($allowHtml) {
            // Allow only safe HTML tags
            return strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate and sanitize email
     */
    public static function sanitizeEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Sanitize integer input
     */
    public static function sanitizeInt($input, $min = null, $max = null) {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            return null;
        }
        
        if ($min !== null && $int < $min) {
            return null;
        }
        
        if ($max !== null && $int > $max) {
            return null;
        }
        
        return $int;
    }
    
    /**
     * Validate URL
     */
    public static function sanitizeUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Rate limiting helper
     */
    public static function checkRateLimit($identifier, $maxAttempts = 10, $timeWindow = 3600) {
        $key = "rate_limit_" . md5($identifier);
        $currentTime = time();
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => $currentTime];
        
        // Reset if time window has passed
        if ($currentTime - $attempts['first_attempt'] > $timeWindow) {
            $attempts = ['count' => 0, 'first_attempt' => $currentTime];
        }
        
        $attempts['count']++;
        $_SESSION[$key] = $attempts;
        
        return $attempts['count'] <= $maxAttempts;
    }
}
```

### Usage Example

```php
function SecureDataProcessor(Request $request) {
    // Rate limiting
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (!SecurityHelper::checkRateLimit($clientIp, 5, 300)) { // 5 attempts per 5 minutes
        http_response_code(429);
        return "<div class='error'>Too many requests. Please try again later.</div>";
    }
    
    // Input validation and sanitization
    $name = SecurityHelper::sanitizeString($request->input('name'));
    $email = SecurityHelper::sanitizeEmail($request->input('email'));
    $age = SecurityHelper::sanitizeInt($request->input('age'), 13, 120);
    $website = SecurityHelper::sanitizeUrl($request->input('website'));
    
    // Validation
    if (empty($name)) {
        return "<div class='error'>Name is required</div>";
    }
    
    if (!$email) {
        return "<div class='error'>Valid email is required</div>";
    }
    
    if ($age === null) {
        return "<div class='error'>Age must be between 13 and 120</div>";
    }
    
    // Process validated data
    return <<<HTML
        <div class="success">
            <h2>✅ Data Processed Successfully</h2>
            <p>Name: {$name}</p>
            <p>Email: {$email}</p>
            <p>Age: {$age}</p>
            {$website ? "<p>Website: {$website}</p>" : ""}
        </div>
    HTML;
}
```

---

## 🔐 Authentication & Authorization

### Session Management

```php
use phpSPA\Http\Session;

class AuthManager {
    public static function login($username, $password) {
        // Validate credentials (implement your logic)
        $user = self::validateCredentials($username, $password);
        
        if ($user) {
            // Start secure session
            Session::start();
            Session::regenerateId(); // Prevent session fixation
            
            // Store user data
            Session::set('user_id', $user['id']);
            Session::set('user_role', $user['role']);
            Session::set('login_time', time());
            
            return true;
        }
        
        return false;
    }
    
    public static function logout() {
        Session::destroy(); // Complete session cleanup
    }
    
    public static function isAuthenticated() {
        return Session::isActive() && Session::has('user_id');
    }
    
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => Session::get('user_id'),
            'role' => Session::get('user_role'),
            'login_time' => Session::get('login_time')
        ];
    }
    
    public static function hasRole($role) {
        $user = self::getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            throw new Exception('Authentication required');
        }
    }
    
    public static function requireRole($role) {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            http_response_code(403);
            throw new Exception('Insufficient permissions');
        }
    }
}
```

### Protected Components

```php
function AdminDashboard() {
    try {
        // Require admin role
        AuthManager::requireRole('admin');
        
        $user = AuthManager::getCurrentUser();
        $loginDateTime = date('Y-m-d H:i', $user['login_time']);
        
        return <<<HTML
            <div class="admin-dashboard">
                <h1>🔒 Admin Dashboard</h1>
                <p>Welcome, Admin! Logged in since {$loginDateTime}</p>
                
                <div class="admin-actions">
                    <button onclick="manageUsers()">Manage Users</button>
                    <button onclick="viewLogs()">View Logs</button>
                    <button onclick="systemSettings()">Settings</button>
                </div>
            </div>
        HTML;
        
    } catch (Exception $e) {
        return <<<HTML
            <div class="access-denied">
                <h2>🚫 Access Denied</h2>
                <p>{$e->getMessage()}</p>
                <Component.Link to="/login" label="Login" />
            </div>
        HTML;
    }
}

function UserProfile() {
    try {
        AuthManager::requireAuth();
        $user = AuthManager::getCurrentUser();
        
        return <<<HTML
            <div class="user-profile">
                <h1>👤 User Profile</h1>
                <p>User ID: {$user['id']}</p>
                <p>Role: {$user['role']}</p>
                
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
        
    } catch (Exception $e) {
        return <<<HTML
            <div class="login-required">
                <h2>🔐 Login Required</h2>
                <Component.Link to="/login" label="Please log in to continue" />
            </div>
        HTML;
    }
}
```

---

## 🔒 SQL Injection Prevention

Always use prepared statements and parameterized queries:

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
    
    /**
     * Safe user lookup
     */
    public function findUser($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Safe user creation
     */
    public function createUser($username, $email, $passwordHash) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$username, $email, $passwordHash]);
    }
    
    /**
     * Safe search with LIKE
     */
    public function searchUsers($searchTerm) {
        $searchTerm = '%' . $searchTerm . '%';
        $stmt = $this->pdo->prepare(
            "SELECT username, email FROM users WHERE username LIKE ? OR email LIKE ? LIMIT 50"
        );
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Safe pagination
     */
    public function getUsers($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
```

### Secure Component with Database

```php
function UserSearch() {
    $searchTerm = createState('search_term', '');
    $results = createState('search_results', []);
    
    // Safely search users if search term is provided
    if (!empty($searchTerm) && strlen($searchTerm) >= 3) {
        $db = new SecureDatabase($dsn, $dbUser, $dbPass);
        $searchResults = $db->searchUsers($searchTerm);
    } else {
        $searchResults = [];
    }
    
    $resultItems = array_map(function($user) {
        $escapedUsername = htmlspecialchars($user['username']);
        $escapedEmail = htmlspecialchars($user['email']);
        
        return <<<HTML
            <div class="user-result">
                <strong>{$escapedUsername}</strong>
                <span>{$escapedEmail}</span>
            </div>
        HTML;
    }, $searchResults);
    
    $resultItemsHtml = implode('', $resultItems);
    
    return <<<HTML
        <div class="user-search">
            <h2>🔍 User Search</h2>
            
            <input 
                type="text" 
                placeholder="Search users..." 
                value="{htmlspecialchars($searchTerm)}"
                onkeyup="handleSearch(this.value)"
                minlength="3"
            >
            
            <div class="search-results">
                {$resultItemsHtml}
            </div>
        </div>
        
        <script data-type="phpspa/script">
            let searchTimeout;
            
            function handleSearch(term) {
                // Debounce search requests
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(() => {
                    if (term.length >= 3) {
                        phpspa.setState('search_term', term);
                    }
                }, 300);
            }
        </script>
    HTML;
}
```

---

## 🔐 Security Headers

phpSPA automatically sets security headers, but you can customize them:

### Automatic Security Headers

```php
class SecurityHeaders {
    public static function apply() {
        // Prevent XSS attacks
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // HTTPS enforcement (in production)
        if (self::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://unpkg.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "font-src 'self'",
            "frame-ancestors 'none'"
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    private static function isProduction() {
        return !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) && 
               !str_ends_with($_SERVER['HTTP_HOST'], '.local');
    }
}

// Apply headers automatically
SecurityHeaders::apply();
```

### Custom CSP for Components

```php
function MediaGallery() {
    // Add specific CSP for this component
    header("Content-Security-Policy: img-src 'self' https://images.example.com https://cdn.example.com");
    
    return <<<HTML
        <div class="media-gallery">
            <h2>📷 Media Gallery</h2>
            <!-- Safe to load images from approved domains -->
        </div>
    HTML;
}
```

---

## 🔍 Security Auditing

### Logging Security Events

```php
class SecurityLogger {
    public static function logAuthAttempt($username, $success, $ip) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => 'auth_attempt',
            'username' => $username,
            'success' => $success,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        error_log(json_encode($logEntry), 3, '/var/log/security.log');
    }
    
    public static function logCsrfViolation($formName, $ip) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => 'csrf_violation',
            'form' => $formName,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        error_log(json_encode($logEntry), 3, '/var/log/security.log');
    }
    
    public static function logRateLimitExceeded($identifier, $ip) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => 'rate_limit_exceeded',
            'identifier' => $identifier,
            'ip' => $ip
        ];
        
        error_log(json_encode($logEntry), 3, '/var/log/security.log');
    }
}
```

### Security Monitoring Component

```php
function SecurityDashboard() {
    AuthManager::requireRole('admin');
    
    // Read security logs (implement safely)
    $recentEvents = getRecentSecurityEvents();
    
    return <<<HTML
        <div class="security-dashboard">
            <h1>🔒 Security Dashboard</h1>
            
            <div class="security-stats">
                <div class="stat-card">
                    <h3>Failed Logins (24h)</h3>
                    <span class="stat-number">{$recentEvents['failed_logins']}</span>
                </div>
                
                <div class="stat-card">
                    <h3>CSRF Violations (24h)</h3>
                    <span class="stat-number">{$recentEvents['csrf_violations']}</span>
                </div>
                
                <div class="stat-card">
                    <h3>Rate Limit Hits (24h)</h3>
                    <span class="stat-number">{$recentEvents['rate_limits']}</span>
                </div>
            </div>
            
            <div class="recent-events">
                <h2>Recent Security Events</h2>
                <!-- Display recent events safely -->
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
    4. **Implement Rate Limiting**: Prevent brute force and DoS attacks
    5. **Log Security Events**: Monitor and audit security-related activities

!!! info "Production Security"
    
    1. **Use HTTPS**: Always encrypt data in transit
    2. **Set Security Headers**: Implement CSP, HSTS, and other protective headers
    3. **Regular Updates**: Keep PHP, dependencies, and server software updated
    4. **Environment Variables**: Store secrets in environment variables, not code
    5. **Access Controls**: Implement proper authentication and authorization

!!! success "Monitoring & Response"
    
    1. **Monitor Logs**: Regularly review security logs for anomalies
    2. **Set Up Alerts**: Automated alerts for security violations
    3. **Incident Response**: Have a plan for security breaches
    4. **Regular Audits**: Periodic security assessments and penetration testing
    5. **Security Training**: Keep your team updated on security best practices

---

## 🚀 Next Steps

Explore specific security topics:

<div class="buttons" markdown>
[CSRF Protection](csrf-protection.md){ .md-button .md-button--primary }
[Request Handling](request-handling.md){ .md-button }
[Input Validation](input-validation.md){ .md-button }
[Best Practices](best-practices.md){ .md-button }
</div>

---

## 💡 Security Principles

**Security is not optional in modern web applications.** phpSPA provides:

- **🛡️ Built-in Protection**: CSRF, XSS, and injection prevention by default
- **🔒 Secure Components**: Pre-built security components for common needs
- **⚡ Performance**: Security features that don't slow down your application
- **🧩 Flexibility**: Customizable security policies for specific requirements
- **📊 Monitoring**: Built-in logging and auditing capabilities

Remember: **Security is a process, not a product.** Stay vigilant, keep learning, and regularly update your security practices!
