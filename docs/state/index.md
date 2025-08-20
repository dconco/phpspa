# 🧠 State Management - Reactive Data Made Simple

State management in phpSPA brings **React-like reactivity** to PHP applications. Create data that automatically updates your UI when it changes — no manual DOM manipulation required.

!!! success "Reactive Philosophy"
    
    **"Data flows down, events flow up"** — State changes trigger automatic re-renders, making your applications feel smooth and responsive like modern JavaScript frameworks.

---

## 🎯 What is State?

**State** is data that can change over time and triggers component re-renders when updated. In phpSPA, state bridges the gap between your PHP backend and JavaScript frontend seamlessly.

### State Flow Diagram

```mermaid
graph LR
    A[PHP Component] --> B[createState()]
    B --> C[Initial Value Set]
    C --> D[HTML Rendered]
    D --> E[User Interaction]
    E --> F[phpspa.setState()]
    F --> G[AJAX Request]
    G --> H[Component Re-render]
    H --> I[DOM Updated]
    I --> D
    
    style B fill:#667eea
    style F fill:#764ba2
    style H fill:#4caf50
```

---

## 🚀 Creating State

Use the `createState()` function to create reactive state variables:

### Basic State Creation

```php
use function phpSPA\Component\createState;

function Counter() {
    // Create state with key and default value
    $count = createState('counter', 0);
    
    return <<<HTML
        <div class="counter">
            <h2>Count: {$count}</h2>
            <button onclick="phpspa.setState('counter', {$count} + 1)">
                Increment
            </button>
        </div>
    HTML;
}
```

### State with Complex Data

```php
function UserProfile() {
    // Object/array state
    $user = createState('user', [
        'name' => 'Guest',
        'email' => '',
        'avatar' => '/default-avatar.png'
    ]);
    
    // Multiple state variables
    $isEditing = createState('editing', false);
    $notifications = createState('notifications', []);
    
    return <<<HTML
        <div class="user-profile">
            <img src="{$user['avatar']}" alt="Avatar">
            <h2>{$user['name']}</h2>
            <p>{$user['email']}</p>
            
            <button onclick="toggleEdit()">
                {$isEditing ? 'Save' : 'Edit'}
            </button>
            
            <div class="notifications">
                {count($notifications)} notifications
            </div>
        </div>
        
        <script data-type="phpspa/script">
            function toggleEdit() {
                const current = phpspa.getState('editing');
                phpspa.setState('editing', !current);
            }
        </script>
    HTML;
}
```

---

## 🔄 Updating State

State is updated from the frontend using JavaScript functions:

### Simple State Updates

```javascript
// Set a new value
phpspa.setState('counter', 10);

// Update based on current value
phpspa.setState('counter', phpspa.getState('counter') + 1);

// Boolean toggle
phpspa.setState('isVisible', !phpspa.getState('isVisible'));
```

### Object State Updates

```javascript
// Update entire object
phpspa.setState('user', {
    name: 'John Doe',
    email: 'john@example.com',
    avatar: '/john-avatar.jpg'
});

// Update specific property (merge)
const currentUser = phpspa.getState('user');
phpspa.setState('user', {
    ...currentUser,
    name: 'Updated Name'
});
```

### Array State Updates

```javascript
// Add item to array
const todos = phpspa.getState('todos');
phpspa.setState('todos', [...todos, newTodo]);

// Remove item from array
const filtered = phpspa.getState('todos').filter(todo => todo.id !== targetId);
phpspa.setState('todos', filtered);

// Update item in array
const updated = phpspa.getState('todos').map(todo => 
    todo.id === targetId ? { ...todo, completed: true } : todo
);
phpspa.setState('todos', updated);
```

---

## 🧩 State Patterns

### 1. **Local Component State**

State that belongs to a specific component:

```php
function TodoItem($todo) {
    // Local state for this specific todo
    $isEditing = createState("todo_{$todo['id']}_editing", false);
    $editText = createState("todo_{$todo['id']}_text", $todo['text']);
    
    $displayText = $isEditing ? 
        "<input value='{$editText}' onchange='updateText({$todo['id']}, this.value)'>" :
        "<span onclick='startEdit({$todo['id']})'>{$todo['text']}</span>";
    
    return <<<HTML
        <div class="todo-item">
            {$displayText}
            <button onclick="toggleEdit({$todo['id']})">
                {$isEditing ? 'Save' : 'Edit'}
            </button>
        </div>
        
        <script data-type="phpspa/script">
            function startEdit(id) {
                phpspa.setState(`todo_${id}_editing`, true);
            }
            
            function toggleEdit(id) {
                const isEditing = phpspa.getState(`todo_${id}_editing`);
                phpspa.setState(`todo_${id}_editing`, !isEditing);
            }
            
            function updateText(id, text) {
                phpspa.setState(`todo_${id}_text`, text);
            }
        </script>
    HTML;
}
```

### 2. **Shared Global State**

State that multiple components can access:

```php
// Component A
function Header() {
    $user = createState('global_user', null);
    $cartCount = createState('cart_count', 0);
    
    return <<<HTML
        <header>
            <h1>My Store</h1>
            <div>Welcome, {$user['name'] ?? 'Guest'}</div>
            <div>Cart: {$cartCount} items</div>
        </header>
    HTML;
}

// Component B
function ProductCard($product) {
    $cartCount = createState('cart_count', 0); // Same key = shared state
    
    return <<<HTML
        <div class="product-card">
            <h3>{$product['name']}</h3>
            <button onclick="addToCart({$product['id']})">
                Add to Cart
            </button>
        </div>
        
        <script data-type="phpspa/script">
            function addToCart(productId) {
                const currentCount = phpspa.getState('cart_count');
                phpspa.setState('cart_count', currentCount + 1);
                
                // Also update cart items
                const cartItems = phpspa.getState('cart_items') || [];
                phpspa.setState('cart_items', [...cartItems, productId]);
            }
        </script>
    HTML;
}
```

### 3. **Computed State**

Derived state based on other state values:

```php
function ShoppingCart() {
    $cartItems = createState('cart_items', []);
    $products = createState('products', []);
    
    // Compute derived values
    $cartProducts = array_filter($products, fn($p) => in_array($p['id'], $cartItems));
    $totalPrice = array_sum(array_column($cartProducts, 'price'));
    $itemCount = count($cartItems);
    $taxAmount = $totalPrice * 0.1;
    $finalTotal = $totalPrice + $taxAmount;
    
    // Prepare formatted strings outside heredoc
    $cartProductsList = implode('', array_map(fn($p) => "<div>{$p['name']} - ${$p['price']}</div>", $cartProducts));
    $formattedSubtotal = number_format($totalPrice, 2);
    $formattedTax = number_format($taxAmount, 2);
    $formattedTotal = number_format($finalTotal, 2);
    
    return <<<HTML
        <div class="shopping-cart">
            <h2>Shopping Cart ({$itemCount} items)</h2>
            
            <div class="cart-items">
                {$cartProductsList}
            </div>
            
            <div class="cart-summary">
                <div>Subtotal: ${$formattedSubtotal}</div>
                <div>Tax: ${$formattedTax}</div>
                <div><strong>Total: ${$formattedTotal}</strong></div>
            </div>
            
            <button onclick="checkout()">Checkout</button>
        </div>
    HTML;
}
```

---

## 📊 State Mapping

Transform arrays into HTML using the `map()` method:

### Basic Mapping

```php
function ItemList() {
    $items = createState('items', ['Apple', 'Banana', 'Cherry']);
    
    // Map array to HTML elements
    $itemElements = array_map(function($item) {
        return "<li onclick='selectItem(\"{$item}\")'>{$item}</li>";
    }, $items);
    
    $itemListHtml = implode('', $itemElements);
    
    return <<<HTML
        <div class="item-list">
            <h2>Items</h2>
            <ul>
                {$itemListHtml}
            </ul>
        </div>
        
        <script data-type="phpspa/script">
            function selectItem(item) {
                alert(`You selected: ${item}`);
            }
        </script>
    HTML;
}
```

### Advanced Mapping with Complex Data

```php
function ProductGrid() {
    $products = createState('products', [
        ['id' => 1, 'name' => 'Laptop', 'price' => 999, 'category' => 'Electronics'],
        ['id' => 2, 'name' => 'Book', 'price' => 20, 'category' => 'Education'],
        ['id' => 3, 'name' => 'Headphones', 'price' => 150, 'category' => 'Electronics']
    ]);
    
    $filter = createState('category_filter', 'all');
    
    // Filter and map products
    $filteredProducts = array_filter($products, function($product) use ($filter) {
        return $filter === 'all' || $product['category'] === $filter;
    });
    
    $productCards = array_map(function($product) {
        return <<<HTML
            <div class="product-card" data-id="{$product['id']}">
                <h3>{$product['name']}</h3>
                <p class="price">${$product['price']}</p>
                <p class="category">{$product['category']}</p>
                <button onclick="addToCart({$product['id']})">Add to Cart</button>
            </div>
        HTML;
    }, $filteredProducts);
    
    $productCardsHtml = implode('', $productCards);
    
    return <<<HTML
        <div class="product-grid">
            <div class="filters">
                <button onclick="setFilter('all')" class="{$filter === 'all' ? 'active' : ''}">
                    All
                </button>
                <button onclick="setFilter('Electronics')" class="{$filter === 'Electronics' ? 'active' : ''}">
                    Electronics
                </button>
                <button onclick="setFilter('Education')" class="{$filter === 'Education' ? 'active' : ''}">
                    Education
                </button>
            </div>
            
            <div class="products">
                {$productCardsHtml}
            </div>
        </div>
        
        <script data-type="phpspa/script">
            function setFilter(category) {
                phpspa.setState('category_filter', category);
            }
            
            function addToCart(productId) {
                const cart = phpspa.getState('cart') || [];
                phpspa.setState('cart', [...cart, productId]);
                alert('Added to cart!');
            }
        </script>
    HTML;
}
```

---

## ⚡ Performance Optimization

### 1. **State Key Strategy**

Use descriptive, consistent state keys:

```php
// ✅ Good - Clear, specific keys
$userProfile = createState('user_profile', null);
$isUserProfileLoading = createState('user_profile_loading', false);
$userPreferences = createState('user_preferences', []);

// ❌ Bad - Generic, confusing keys
$data = createState('data', null);
$loading = createState('loading', false);
$stuff = createState('stuff', []);
```

### 2. **Minimal State Updates**

Only create state for data that actually changes:

```php
function OptimizedComponent($staticData) {
    // ✅ Only create state for dynamic data
    $count = createState('counter', 0);
    $isVisible = createState('visibility', true);
    
    // ✅ Use regular variables for static data
    $title = $staticData['title'];
    $description = $staticData['description'];
    
    return <<<HTML
        <div class="component">
            <h2>{$title}</h2>
            <p>{$description}</p>
            
            {$isVisible ? "<div>Count: {$count}</div>" : ''}
            
            <button onclick="increment()">Increment</button>
            <button onclick="toggle()">Toggle</button>
        </div>
        
        <script data-type="phpspa/script">
            function increment() {
                phpspa.setState('counter', {$count} + 1);
            }
            
            function toggle() {
                phpspa.setState('visibility', !{$isVisible});
            }
        </script>
    HTML;
}
```

### 3. **Batched State Updates**

Group related state updates together:

```javascript
// ✅ Good - Batch related updates
function updateUserProfile(userData) {
    phpspa.setState('user_name', userData.name);
    phpspa.setState('user_email', userData.email);
    phpspa.setState('user_avatar', userData.avatar);
    phpspa.setState('profile_loading', false);
}

// ⚠️ Consider - Single state object for related data
function updateUserProfileBetter(userData) {
    phpspa.setState('user_profile', {
        name: userData.name,
        email: userData.email,
        avatar: userData.avatar
    });
    phpspa.setState('profile_loading', false);
}
```

---

## 🔄 State Persistence

### Session Storage Integration

```php
function PersistentCounter() {
    // Load from session or use default
    $count = createState('persistent_counter', $_SESSION['counter'] ?? 0);
    
    return <<<HTML
        <div class="persistent-counter">
            <h2>Persistent Count: {$count}</h2>
            <button onclick="increment()">Increment</button>
            <button onclick="reset()">Reset</button>
        </div>
        
        <script data-type="phpspa/script">
            function increment() {
                const newCount = {$count} + 1;
                phpspa.setState('persistent_counter', newCount);
                
                // Save to session
                fetch('/save-counter', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ count: newCount })
                });
            }
            
            function reset() {
                phpspa.setState('persistent_counter', 0);
                
                fetch('/save-counter', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ count: 0 })
                });
            }
        </script>
    HTML;
}
```

### Local Storage Integration

```javascript
// Save state to localStorage
function saveToLocalStorage(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
    phpspa.setState(key, value);
}

// Load state from localStorage
function loadFromLocalStorage(key, defaultValue) {
    const stored = localStorage.getItem(key);
    const value = stored ? JSON.parse(stored) : defaultValue;
    phpspa.setState(key, value);
    return value;
}

// Usage in component
function PersistentSettings() {
    // Load settings on component mount
    const settings = loadFromLocalStorage('user_settings', {
        theme: 'light',
        notifications: true
    });
    
    return <<<HTML
        <script data-type="phpspa/script">
            function updateTheme(theme) {
                const settings = phpspa.getState('user_settings');
                const newSettings = { ...settings, theme };
                saveToLocalStorage('user_settings', newSettings);
            }
        </script>
    HTML;
}
```

---

## 🧪 State Testing

### Unit Testing State Logic

```php
class StateTest {
    public function testCounterIncrement() {
        // Mock state functions
        $mockState = ['counter' => 5];
        
        // Test component with mock state
        $component = new Counter();
        $result = $component->render($mockState);
        
        $this->assertStringContains('Count: 5', $result);
    }
    
    public function testStateMapping() {
        $items = ['Apple', 'Banana', 'Cherry'];
        $component = new ItemList();
        $result = $component->render(['items' => $items]);
        
        foreach ($items as $item) {
            $this->assertStringContains($item, $result);
        }
    }
}
```

### Integration Testing

```javascript
// Test state updates in browser
describe('State Management', () => {
    test('counter increments correctly', async () => {
        phpspa.setState('counter', 0);
        
        // Simulate button click
        const incrementBtn = document.querySelector('[onclick="increment()"]');
        incrementBtn.click();
        
        // Wait for state update
        await new Promise(resolve => setTimeout(resolve, 100));
        
        expect(phpspa.getState('counter')).toBe(1);
    });
    
    test('array state updates correctly', async () => {
        phpspa.setState('todos', []);
        
        const newTodo = { id: 1, text: 'Test todo', completed: false };
        const todos = phpspa.getState('todos');
        phpspa.setState('todos', [...todos, newTodo]);
        
        expect(phpspa.getState('todos')).toHaveLength(1);
        expect(phpspa.getState('todos')[0]).toEqual(newTodo);
    });
});
```

---

## 📚 Best Practices

!!! tip "State Design Principles"
    
    1. **Keep State Minimal**: Only create state for data that changes
    2. **Use Descriptive Keys**: Clear state keys improve maintainability
    3. **Group Related State**: Consider grouping related data into objects
    4. **Avoid Deep Nesting**: Flatten state structure when possible
    5. **Handle Loading States**: Always consider loading and error states

!!! info "Performance Guidelines"
    
    1. **Batch Updates**: Group related state changes together
    2. **Avoid Unnecessary Re-renders**: Don't update state if value hasn't changed
    3. **Use Computed Values**: Calculate derived data in PHP, not state
    4. **Clean Up State**: Remove state that's no longer needed
    5. **Profile State Usage**: Monitor which state updates cause re-renders

!!! success "Security Considerations"
    
    1. **Validate State Updates**: Always validate data before setting state
    2. **Sanitize User Input**: Clean data before storing in state
    3. **Protect Sensitive Data**: Don't store secrets in client-side state
    4. **Use CSRF Protection**: Protect state-changing operations
    5. **Implement Rate Limiting**: Prevent abuse of state update endpoints

---

## 🚀 Advanced State Patterns

### State Machines

```php
function StateMachine() {
    $status = createState('form_status', 'idle'); // idle, loading, success, error
    $data = createState('form_data', null);
    $error = createState('form_error', null);
    
    $content = match($status) {
        'idle' => '<form onsubmit="submitForm(event)">...</form>',
        'loading' => '<div class="spinner">Submitting...</div>',
        'success' => '<div class="success">Form submitted successfully!</div>',
        'error' => "<div class='error'>Error: {$error}</div>",
        default => '<div>Unknown state</div>'
    };
    
    return <<<HTML
        <div class="state-machine">
            {$content}
        </div>
        
        <script data-type="phpspa/script">
            async function submitForm(event) {
                event.preventDefault();
                
                phpspa.setState('form_status', 'loading');
                
                try {
                    const response = await fetch('/submit', {
                        method: 'POST',
                        body: new FormData(event.target)
                    });
                    
                    if (response.ok) {
                        phpspa.setState('form_status', 'success');
                    } else {
                        throw new Error('Submission failed');
                    }
                } catch (error) {
                    phpspa.setState('form_status', 'error');
                    phpspa.setState('form_error', error.message);
                }
            }
        </script>
    HTML;
}
```

### Observer Pattern

```php
function StateObserver() {
    $observers = createState('state_observers', []);
    $globalData = createState('global_data', null);
    
    return <<<HTML
        <div class="observer-pattern">
            <button onclick="updateGlobalData()">Update Data</button>
            <div id="observer-log"></div>
        </div>
        
        <script data-type="phpspa/script">
            // Register observer
            function registerObserver(callback) {
                const observers = phpspa.getState('state_observers');
                phpspa.setState('state_observers', [...observers, callback]);
            }
            
            // Notify all observers
            function notifyObservers(data) {
                const observers = phpspa.getState('state_observers');
                observers.forEach(callback => callback(data));
            }
            
            function updateGlobalData() {
                const newData = { timestamp: Date.now(), random: Math.random() };
                phpspa.setState('global_data', newData);
                notifyObservers(newData);
            }
            
            // Register a logger observer
            registerObserver((data) => {
                console.log('State updated:', data);
                document.getElementById('observer-log').innerHTML += 
                    `<div>Data updated at ${new Date(data.timestamp).toLocaleTimeString()}</div>`;
            });
        </script>
    HTML;
}
```

---

## 🔗 Next Steps

Master state management and explore related topics:

<div class="buttons" markdown>
[Creating State](creating-state.md){ .md-button .md-button--primary }
[Updating State](updating-state.md){ .md-button }
[State Mapping](state-mapping.md){ .md-button }
[Loading States](loading-states.md){ .md-button }
</div>

---

## 💡 Key Takeaways

**State management is the heart of reactive applications.** With phpSPA:

- **🔄 Automatic Updates**: State changes trigger component re-renders
- **🧠 Simple API**: `createState()` in PHP, `setState()` in JavaScript
- **⚡ Performance**: Efficient updates with minimal DOM changes
- **🧩 Flexible**: Supports simple values, objects, and complex data structures
- **🔗 Connected**: Seamless bridge between PHP backend and JavaScript frontend

Master state management, and your applications will feel smooth, responsive, and modern!
