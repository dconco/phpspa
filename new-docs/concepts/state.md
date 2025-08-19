# :brain: State Management

Manage **reactive state** that automatically updates your UI when data changes. phpSPA's state system works like React's `useState` but for PHP.

---

## :thought_balloon: **Core Concepts**

### What is State?
State represents data that can change over time - user input, API responses, UI states, etc. When state changes, phpSPA automatically re-renders affected components.

### Reactive Updates
Unlike traditional PHP, phpSPA state changes trigger automatic UI updates without page reloads, creating a smooth user experience.

---

## :gear: **Basic State Usage**

### Creating State

```php title="Simple Counter State"
<?php
use function Component\createState;

function Counter() {
    // Create state with key and default value
    $count = createState('counter', 0);
    $countValue = $count->getValue();
    
    return <<<HTML
        <div class="counter">
            <h2>Count: {$countValue}</h2>
            <button onclick="phpspa.setState('counter', {$countValue} + 1)">
                Increment
            </button>
            <button onclick="phpspa.setState('counter', {$countValue} - 1)">
                Decrement
            </button>
        </div>
    HTML;
}
```

### State Persistence
State automatically persists across page reloads using session storage:

```php title="Persistent User Preferences"
<?php
function UserSettings() {
    // State persists even after page refresh
    $theme = createState('theme', 'light');
    $language = createState('language', 'en');
    
    $themeValue = $theme->getValue();
    $languageValue = $language->getValue();
    
    return <<<HTML
        <div class="settings">
            <p>Current Theme: {$themeValue}</p>
            <p>Language: {$languageValue}</p>
            
            <button onclick="phpspa.setState('theme', 'dark')">
                Dark Theme
            </button>
            <button onclick="phpspa.setState('theme', 'light')">
                Light Theme
            </button>
        </div>
    HTML;
}
```

---

## :file_cabinet: **Complex State**

### Object State
Store and manage complex data structures:

```php title="User Profile State"
<?php
function UserProfile() {
    // Complex object state
    $user = createState('user', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => '/default-avatar.png'
    ]);
    
    $userData = $user->getValue();
    $userName = $userData['name'];
    $userEmail = $userData['email'];
    
    return <<<HTML
        <div class="profile">
            <h2>Welcome, {$userName}!</h2>
            <p>Email: {$userEmail}</p>
            
            <button onclick="updateUserName()">
                Change Name
            </button>
            
            <script>
                function updateUserName() {
                    const newName = prompt('Enter new name:');
                    if (newName) {
                        const currentUser = phpspa.getState('user');
                        currentUser.name = newName;
                        phpspa.setState('user', currentUser);
                    }
                }
            </script>
        </div>
    HTML;
}
```

### Array State
Manage lists and collections:

```php title="Todo List State"
<?php
function TodoList() {
    $todos = createState('todos', []);
    $todoItems = $todos->getValue();
    
    $todoHtml = '';
    foreach ($todoItems as $index => $todo) {
        $todoText = $todo['text'];
        $completed = $todo['completed'] ? 'completed' : '';
        
        $todoHtml .= <<<HTML
            <li class="todo-item {$completed}">
                <span>{$todoText}</span>
                <button onclick="toggleTodo({$index})">
                    Toggle
                </button>
                <button onclick="deleteTodo({$index})">
                    Delete
                </button>
            </li>
        HTML;
    }
    
    return <<<HTML
        <div class="todo-app">
            <h2>Todo List</h2>
            <ul class="todo-list">
                {$todoHtml}
            </ul>
            
            <div class="add-todo">
                <input type="text" id="todoInput" placeholder="Add new todo...">
                <button onclick="addTodo()">Add</button>
            </div>
            
            <script>
                function addTodo() {
                    const input = document.getElementById('todoInput');
                    const text = input.value.trim();
                    
                    if (text) {
                        const todos = phpspa.getState('todos');
                        todos.push({
                            text: text,
                            completed: false
                        });
                        phpspa.setState('todos', todos);
                        input.value = '';
                    }
                }
                
                function toggleTodo(index) {
                    const todos = phpspa.getState('todos');
                    todos[index].completed = !todos[index].completed;
                    phpspa.setState('todos', todos);
                }
                
                function deleteTodo(index) {
                    const todos = phpspa.getState('todos');
                    todos.splice(index, 1);
                    phpspa.setState('todos', todos);
                }
            </script>
        </div>
    HTML;
}
```

---

## :arrows_counterclockwise: **State Updates**

### From JavaScript
Update state from client-side JavaScript:

```php title="Interactive Form"
<?php
function ContactForm() {
    $formData = createState('contactForm', [
        'name' => '',
        'email' => '',
        'message' => ''
    ]);
    
    return <<<HTML
        <form class="contact-form">
            <input 
                type="text" 
                placeholder="Your Name"
                onchange="updateFormField('name', this.value)"
            >
            <input 
                type="email" 
                placeholder="Your Email"
                onchange="updateFormField('email', this.value)"
            >
            <textarea 
                placeholder="Your Message"
                onchange="updateFormField('message', this.value)"
            ></textarea>
            
            <button type="button" onclick="submitForm()">
                Send Message
            </button>
        </form>
        
        <script>
            function updateFormField(field, value) {
                const formData = phpspa.getState('contactForm');
                formData[field] = value;
                phpspa.setState('contactForm', formData);
            }
            
            function submitForm() {
                const formData = phpspa.getState('contactForm');
                
                // Validate form
                if (!formData.name || !formData.email || !formData.message) {
                    alert('Please fill all fields');
                    return;
                }
                
                // Submit to server
                fetch('/api/contact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    alert('Message sent successfully!');
                    // Reset form
                    phpspa.setState('contactForm', {
                        name: '', email: '', message: ''
                    });
                });
            }
        </script>
    HTML;
}
```

### From PHP Functions
Update state using server-side functions:

```php title="Server-Side State Updates"
<?php
use function Component\useFunction;

function ShoppingCart() {
    $cartItems = createState('cart', []);
    $cartData = $cartItems->getValue();
    
    // Create callable PHP function
    $addToCart = useFunction(function($productId, $quantity = 1) {
        $cart = createState('cart', [])->getValue();
        
        // Check if item already exists
        $existingIndex = -1;
        foreach ($cart as $index => $item) {
            if ($item['productId'] === $productId) {
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingIndex >= 0) {
            // Update quantity
            $cart[$existingIndex]['quantity'] += $quantity;
        } else {
            // Add new item
            $cart[] = [
                'productId' => $productId,
                'quantity' => $quantity,
                'addedAt' => time()
            ];
        }
        
        return $cart;
    });
    
    $totalItems = count($cartData);
    
    return <<<HTML
        <div class="shopping-cart">
            <h2>Shopping Cart ({$totalItems} items)</h2>
            
            <button onclick="{$addToCart}(123, 1)">
                Add Product #123
            </button>
            <button onclick="{$addToCart}(456, 2)">
                Add 2x Product #456
            </button>
        </div>
    HTML;
}
```

---

## :gear: **Advanced Patterns**

### Computed State
Derive values from existing state:

```php title="Computed Values"
<?php
function OrderSummary() {
    $cartItems = createState('cartItems', [])->getValue();
    $discount = createState('discount', 0)->getValue();
    
    // Compute total
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $discountAmount = $subtotal * ($discount / 100);
    $total = $subtotal - $discountAmount;
    
    return <<<HTML
        <div class="order-summary">
            <h3>Order Summary</h3>
            <p>Subtotal: \${$subtotal}</p>
            <p>Discount ({$discount}%): -\${$discountAmount}</p>
            <hr>
            <p><strong>Total: \${$total}</strong></p>
            
            <button onclick="applyDiscount(10)">
                Apply 10% Discount
            </button>
            
            <script>
                function applyDiscount(percent) {
                    phpspa.setState('discount', percent);
                }
            </script>
        </div>
    HTML;
}
```

### State Sharing Between Components
Share state across multiple components:

```php title="Shared State Example"
<?php
// Component 1: Display user status
function UserStatus() {
    $user = createState('currentUser', ['loggedIn' => false])->getValue();
    $status = $user['loggedIn'] ? 'Logged In' : 'Guest';
    
    return "<p>Status: {$status}</p>";
}

// Component 2: Login form
function LoginForm() {
    $user = createState('currentUser', ['loggedIn' => false])->getValue();
    
    if ($user['loggedIn']) {
        return <<<HTML
            <div>
                <p>Welcome back!</p>
                <button onclick="logout()">Logout</button>
            </div>
        HTML;
    }
    
    return <<<HTML
        <form>
            <input type="text" id="username" placeholder="Username">
            <input type="password" id="password" placeholder="Password">
            <button type="button" onclick="login()">Login</button>
        </form>
        
        <script>
            function login() {
                // Simulate login
                phpspa.setState('currentUser', {
                    loggedIn: true,
                    username: document.getElementById('username').value
                });
            }
            
            function logout() {
                phpspa.setState('currentUser', {
                    loggedIn: false,
                    username: null
                });
            }
        </script>
    HTML;
}
```

---

## :shield: **Best Practices**

!!! tip "State Management Tips"

    **🔑 Use unique keys**
    Always use descriptive, unique keys for your state to avoid conflicts

    **📦 Keep state minimal**
    Only store what you need to re-render - derive everything else

    **🔄 Batch updates**
    Group related state changes together for better performance

    **🧹 Clean up unused state**
    Remove state that's no longer needed to prevent memory leaks

### Naming Conventions

```php title="Good State Naming"
<?php
// Good: descriptive and unique
$userProfile = createState('userProfile', []);
$shoppingCart = createState('shoppingCart', []);
$searchResults = createState('searchResults', []);

// Bad: generic or conflicting
$data = createState('data', []);
$state = createState('state', []);
$items = createState('items', []); // Too generic
```

### Performance Optimization

```php title="Optimized State Updates"
<?php
function OptimizedComponent() {
    // Use specific state for specific purposes
    $loading = createState('isLoading', false);
    $error = createState('errorMessage', null);
    $data = createState('userData', []);
    
    // Instead of one large state object
    // $appState = createState('appState', [
    //     'loading' => false,
    //     'error' => null,
    //     'data' => []
    // ]);
}
```

---

## :question: **Common Issues**

!!! warning "Troubleshooting State"

    **State not updating?**
    
    1. Check that you're using unique state keys
    2. Verify JavaScript includes `phpspa-js` library
    3. Make sure you're calling `setState` not just `getState`
    
    **State resets on page load?**
    
    1. This is normal behavior - state persists in session
    2. Use `localStorage` for permanent persistence
    3. Initialize state with data from database if needed

### Debugging State

```php title="Debug State Changes"
<?php
function DebugComponent() {
    $debugState = createState('debug', ['lastUpdate' => time()]);
    
    return <<<HTML
        <div class="debug-panel">
            <h3>Debug Info</h3>
            <button onclick="logAllState()">Log All State</button>
            <button onclick="clearAllState()">Clear All State</button>
        </div>
        
        <script>
            function logAllState() {
                console.log('All State:', phpspa.getAllState());
            }
            
            function clearAllState() {
                phpspa.clearAllState();
                location.reload();
            }
        </script>
    HTML;
}
```

---

!!! success "State Mastery!"
    You now understand phpSPA's reactive state system. Next, explore [Security Features →](../security/csrf.md) to learn about protecting your applications with CSRF tokens and other security measures.
