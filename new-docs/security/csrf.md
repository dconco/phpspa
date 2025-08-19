# :shield: CSRF Protection

Protect your applications from **Cross-Site Request Forgery** attacks with phpSPA's built-in security system. Easy to use, secure by default.

---

## :warning: **What is CSRF?**

Cross-Site Request Forgery (CSRF) is an attack where malicious websites trick users into performing unwanted actions on applications they're authenticated to.

### Example Attack
1. User logs into your bank website
2. User visits malicious website (in another tab)
3. Malicious site submits hidden form to bank
4. Bank thinks request is legitimate (user is logged in)
5. Money transferred without user consent

**phpSPA prevents this** with automatic token validation.

---

## :gear: **Quick Setup**

### Basic Form Protection

```php title="Protected Contact Form"
<?php
use Component\Csrf;

function ContactForm() {
    $csrf = new Csrf();
    $csrfInput = $csrf->__render('contact_form');
    
    return <<<HTML
        <form method="post" action="/submit-contact">
            {$csrfInput}
            
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="email" name="email" placeholder="Your Email" required>
            <textarea name="message" placeholder="Your Message" required></textarea>
            
            <button type="submit">Send Message</button>
        </form>
    HTML;
}
```

### Processing Form Submission

```php title="Validate CSRF Token"
<?php
use phpSPA\Core\Helper\CsrfManager;

function handleContactForm() {
    if ($_POST) {
        $csrf = new CsrfManager('contact_form');
        
        // Validate token
        if ($csrf->verify()) {
            // Process form safely
            $name = $_POST['name'];
            $email = $_POST['email'];
            $message = $_POST['message'];
            
            // Save to database, send email, etc.
            return "Message sent successfully!";
        } else {
            return "Security error: Invalid token";
        }
    }
}
```

---

## :lock: **Advanced Features**

### Multiple Forms

Different forms need different tokens to prevent conflicts:

```php title="Multiple Form Protection"
<?php
function LoginForm() {
    $csrf = new Csrf();
    $loginToken = $csrf->__render('login_form');
    
    return <<<HTML
        <form method="post" action="/login">
            {$loginToken}
            <input type="text" name="username" placeholder="Username">
            <input type="password" name="password" placeholder="Password">
            <button type="submit">Login</button>
        </form>
    HTML;
}

function RegisterForm() {
    $csrf = new Csrf();
    $registerToken = $csrf->__render('register_form');
    
    return <<<HTML
        <form method="post" action="/register">
            {$registerToken}
            <input type="text" name="username" placeholder="Username">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Password">
            <button type="submit">Register</button>
        </form>
    HTML;
}
```

### AJAX Form Protection

Protect AJAX requests with CSRF tokens:

```php title="AJAX-Protected Form"
<?php
function AjaxForm() {
    $csrf = new CsrfManager('ajax_form');
    $token = $csrf->getToken();
    
    return <<<HTML
        <form id="ajaxForm">
            <input type="text" id="title" placeholder="Post Title">
            <textarea id="content" placeholder="Post Content"></textarea>
            <button type="button" onclick="submitPost()">Submit</button>
        </form>
        
        <script>
            function submitPost() {
                const formData = {
                    title: document.getElementById('title').value,
                    content: document.getElementById('content').value,
                    csrf_token: '{$token}'
                };
                
                fetch('/api/posts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Post created successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        </script>
    HTML;
}
```

### API Endpoint Protection

```php title="Protected API Endpoint"
<?php
function createPost() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $csrf = new CsrfManager('ajax_form');
        
        if ($csrf->verify($input['csrf_token'])) {
            // Process the request
            $title = $input['title'];
            $content = $input['content'];
            
            // Save to database
            // savePost($title, $content);
            
            echo json_encode(['success' => true, 'message' => 'Post created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        }
    }
}
```

---

## :gear: **Configuration**

### Token Expiration

Control how long tokens remain valid:

```php title="Custom Token Expiration"
<?php
use phpSPA\Core\Helper\CsrfManager;

function CustomCsrf() {
    // Token expires in 30 minutes
    $csrf = new CsrfManager('custom_form', 1800); // 30 * 60 seconds
    
    return $csrf->getInput();
}
```

### Token Reuse

Control whether tokens can be used multiple times:

```php title="Token Reuse Settings"
<?php
function ProcessForm() {
    $csrf = new CsrfManager('my_form');
    
    if ($_POST) {
        // Allow token reuse (don't expire after first use)
        if ($csrf->verify(false)) {
            // Process form - token still valid for future requests
            return "Form processed successfully!";
        }
        
        // Default behavior - token expires after use
        if ($csrf->verify()) {
            // Process form - token no longer valid
            return "Form processed, need new token for next submission";
        }
    }
}
```

### Maximum Tokens

Limit number of stored tokens per session:

```php title="Token Limits"
<?php
// By default, maximum 10 tokens per session
// Oldest tokens are automatically cleaned up

function ManyForms() {
    // Each form gets its own token
    $forms = [];
    for ($i = 1; $i <= 5; $i++) {
        $csrf = new CsrfManager("form_{$i}");
        $forms[$i] = $csrf->getInput();
    }
    
    return $forms;
}
```

---

## :bulb: **Best Practices**

### Form Design

```php title="User-Friendly CSRF"
<?php
function UserFriendlyForm() {
    $csrf = new Csrf();
    $csrfInput = $csrf->__render('user_form');
    
    return <<<HTML
        <form method="post" id="userForm">
            {$csrfInput}
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <button type="submit">Submit</button>
        </form>
        
        <div id="errorMessage" style="display: none; color: red;">
            Session expired. Please refresh the page and try again.
        </div>
        
        <script>
            document.getElementById('userForm').addEventListener('submit', function(e) {
                // You can add client-side validation here
                // CSRF validation happens server-side automatically
            });
        </script>
    HTML;
}
```

### Error Handling

```php title="Graceful Error Handling"
<?php
function SecureFormHandler() {
    if ($_POST) {
        $csrf = new CsrfManager('secure_form');
        
        try {
            if ($csrf->verify()) {
                // Process form
                return processFormData($_POST);
            } else {
                // CSRF failed - show user-friendly message
                return <<<HTML
                    <div class="error-message">
                        <h3>Security Error</h3>
                        <p>Your session has expired for security reasons.</p>
                        <p>Please <a href="javascript:location.reload()">refresh the page</a> and try again.</p>
                    </div>
                HTML;
            }
        } catch (Exception $e) {
            // Log error securely
            error_log("CSRF Error: " . $e->getMessage());
            
            return <<<HTML
                <div class="error-message">
                    <h3>Technical Error</h3>
                    <p>Something went wrong. Please try again later.</p>
                </div>
            HTML;
        }
    }
}
```

---

## :test_tube: **Testing CSRF Protection**

### Manual Testing

```php title="Test CSRF Implementation"
<?php
function TestCsrf() {
    return <<<HTML
        <div class="csrf-test">
            <h3>CSRF Protection Test</h3>
            
            <!-- Valid form with CSRF token -->
            <form method="post" action="/test-csrf">
                <input type="hidden" name="csrf_token" value="valid_token_here">
                <button type="submit" name="action" value="valid">
                    Submit with Valid Token ✅
                </button>
            </form>
            
            <!-- Invalid form without CSRF token -->
            <form method="post" action="/test-csrf">
                <button type="submit" name="action" value="invalid">
                    Submit without Token ❌
                </button>
            </form>
            
            <!-- Form with wrong token -->
            <form method="post" action="/test-csrf">
                <input type="hidden" name="csrf_token" value="wrong_token">
                <button type="submit" name="action" value="wrong">
                    Submit with Wrong Token ❌
                </button>
            </form>
        </div>
    HTML;
}
```

### Automated Testing

```php title="Unit Test for CSRF"
<?php
use PHPUnit\Framework\TestCase;
use phpSPA\Core\Helper\CsrfManager;

class CsrfTest extends TestCase
{
    public function testValidToken()
    {
        $csrf = new CsrfManager('test_form');
        $token = $csrf->getToken();
        
        // Simulate POST with valid token
        $_POST['csrf_token'] = $token;
        
        $this->assertTrue($csrf->verify());
    }
    
    public function testInvalidToken()
    {
        $csrf = new CsrfManager('test_form');
        
        // Simulate POST with invalid token
        $_POST['csrf_token'] = 'invalid_token';
        
        $this->assertFalse($csrf->verify());
    }
    
    public function testExpiredToken()
    {
        // Create CSRF with 1 second expiry
        $csrf = new CsrfManager('test_form', 1);
        $token = $csrf->getToken();
        
        // Wait for token to expire
        sleep(2);
        
        $_POST['csrf_token'] = $token;
        
        $this->assertFalse($csrf->verify());
    }
}
```

---

## :warning: **Security Considerations**

!!! danger "Important Security Notes"

    **🔒 Always validate server-side**
    Never rely on client-side CSRF validation alone

    **⏰ Use reasonable expiry times**
    Balance security with user experience (1 hour is good default)

    **🔄 Regenerate after login**
    Create new tokens after authentication changes

    **📝 Log failed attempts**
    Monitor for potential attacks

### Security Checklist

- ✅ CSRF tokens on all forms
- ✅ Different tokens for different forms  
- ✅ Server-side validation
- ✅ Reasonable token expiry
- ✅ Error logging
- ✅ User-friendly error messages
- ✅ HTTPS in production

---

## :question: **Troubleshooting**

!!! question "Common Issues"

    **Token validation failing?**
    
    1. Check form action points to correct handler
    2. Verify token name matches (default: `csrf_token`)
    3. Ensure session is started
    4. Check token hasn't expired
    
    **Multiple tokens conflict?**
    
    1. Use unique names for each form
    2. Don't reuse token names across pages
    3. Clear old tokens when necessary

### Debug CSRF Issues

```php title="CSRF Debugging"
<?php
function DebugCsrf() {
    $csrf = new CsrfManager('debug_form');
    
    return <<<HTML
        <div class="csrf-debug">
            <h3>CSRF Debug Info</h3>
            
            <p><strong>Session ID:</strong> {$_SESSION['session_id']}</p>
            <p><strong>Current Token:</strong> {$csrf->getToken()}</p>
            <p><strong>Submitted Token:</strong> {$_POST['csrf_token'] ?? 'None'}</p>
            
            <form method="post">
                {$csrf->getInput()}
                <button type="submit">Test Submit</button>
            </form>
            
            <script>
                console.log('CSRF Debug - Session Storage:', sessionStorage);
            </script>
        </div>
    HTML;
}
```

---

!!! success "Your App is Secure!"
    CSRF protection is now active! Your forms are protected from cross-site request forgery attacks. Next, learn about [Performance Optimization →](../performance/compression.md) to make your app lightning fast.
