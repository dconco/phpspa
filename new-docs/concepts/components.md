# :puzzle: Components

**Components are the heart of phpSPA.** They're simple PHP functions that return HTML, making your code reusable, testable, and easy to understand.

---

## :bulb: **What is a Component?**

A component in phpSPA is just a **PHP function that returns HTML string**. That's it! No classes, no inheritance, no complex syntax.

```php
<?php
// This is a component!
function Welcome() {
    return '<h1>Hello, phpSPA!</h1>';
}
```

Unlike traditional PHP where HTML and logic are mixed across multiple files, components keep everything together in a clean, predictable way.

---

## :hammer_and_wrench: **Creating Components**

### Basic Component

```php
<?php
function UserProfile() {
    return <<<HTML
        <div class="user-profile">
            <img src="/avatar.jpg" alt="User Avatar">
            <h2>John Doe</h2>
            <p>Software Developer</p>
        </div>
    HTML;
}
```

### Component with PHP Logic

```php
<?php
function WeatherWidget() {
    $temperature = rand(15, 30);
    $condition = $temperature > 25 ? 'Sunny' : 'Cloudy';
    $icon = $temperature > 25 ? '☀️' : '☁️';
    
    return <<<HTML
        <div class="weather-widget">
            <div class="icon">{$icon}</div>
            <div class="temp">{$temperature}°C</div>
            <div class="condition">{$condition}</div>
        </div>
    HTML;
}
```

### Dynamic Content with Data

```php
<?php
function ProductCard($product) {
    $onSale = $product['sale_price'] ? true : false;
    $price = $onSale ? $product['sale_price'] : $product['price'];
    $saleBadge = $onSale ? '<span class="sale-badge">ON SALE</span>' : '';
    
    return <<<HTML
        <div class="product-card">
            {$saleBadge}
            <img src="{$product['image']}" alt="{$product['name']}">
            <h3>{$product['name']}</h3>
            <p class="price">\${$price}</p>
            <button onclick="addToCart({$product['id']})">
                Add to Cart
            </button>
        </div>
    HTML;
}
```

---

## :arrows_counterclockwise: **Registering Components**

To use a component in your phpSPA app, register it with a route:

```php
<?php
use phpSPA\App;
use phpSPA\Component;

// Create app instance
$app = new App('layout');

// Register a component
$welcome = new Component('Welcome');
$welcome->route('/');

// Register with multiple routes
$profile = new Component('UserProfile');
$profile->route(['/profile', '/user', '/me']);

// Register with HTTP methods
$dashboard = new Component('Dashboard');
$dashboard->route('/dashboard')->method(['GET', 'POST']);

// Attach components and run
$app->attach($welcome)
    ->attach($profile)  
    ->attach($dashboard)
    ->run();
```

---

## :sparkles: **Component Features**

### Conditional Rendering

```php
<?php
function LoginStatus($user = null) {
    if ($user) {
        return <<<HTML
            <div class="user-menu">
                <span>Welcome, {$user['name']}!</span>
                <a href="/logout">Logout</a>
            </div>
        HTML;
    }
    
    return <<<HTML
        <div class="auth-buttons">
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        </div>
    HTML;
}
```

### Loops and Iteration

```php
<?php
function NavigationMenu($items) {
    $menuItems = array_map(function($item) {
        $activeClass = $item['active'] ? 'active' : '';
        return "<li class=\"{$activeClass}\"><a href=\"{$item['url']}\">{$item['title']}</a></li>";
    }, $items);
    
    return <<<HTML
        <nav class="main-navigation">
            <ul>{implode('', $menuItems)}</ul>
        </nav>
    HTML;
}
```

### Nested Components

```php
<?php
function BlogPost($post) {
    return <<<HTML
        <article class="blog-post">
            <header>
                <h1>{$post['title']}</h1>
                {PostMeta($post)}
            </header>
            <div class="content">
                {$post['content']}
            </div>
            <footer>
                {PostTags($post['tags'])}
                {ShareButtons($post)}
            </footer>
        </article>
    HTML;
}

function PostMeta($post) {
    return <<<HTML
        <div class="post-meta">
            <span class="author">By {$post['author']}</span>
            <span class="date">{$post['date']}</span>
            <span class="reading-time">{$post['reading_time']} min read</span>
        </div>
    HTML;
}
```

---

## :recycle: **Component Reusability**

### Configurable Components

```php
<?php
function Button($text, $type = 'primary', $size = 'medium', $attributes = []) {
    $classes = "btn btn-{$type} btn-{$size}";
    $attrs = array_map(fn($k, $v) => "{$k}=\"{$v}\"", array_keys($attributes), $attributes);
    $attrString = implode(' ', $attrs);
    
    return <<<HTML
        <button class="{$classes}" {$attrString}>
            {$text}
        </button>
    HTML;
}

// Usage examples:
echo Button('Save');                           // Primary button
echo Button('Cancel', 'secondary');           // Secondary button  
echo Button('Delete', 'danger', 'small');     // Small danger button
echo Button('Submit', 'primary', 'large', ['onclick' => 'submitForm()']);
```

### Layout Components

```php
<?php
function Card($title, $content, $footer = '') {
    $footerHtml = $footer ? "<div class=\"card-footer\">{$footer}</div>" : '';
    
    return <<<HTML
        <div class="card">
            <div class="card-header">
                <h3>{$title}</h3>
            </div>
            <div class="card-body">
                {$content}
            </div>
            {$footerHtml}
        </div>
    HTML;
}

function Modal($id, $title, $content, $actions = '') {
    return <<<HTML
        <div id="{$id}" class="modal hidden">
            <div class="modal-overlay" onclick="closeModal('{$id}')"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{$title}</h2>
                    <button onclick="closeModal('{$id}')">&times;</button>
                </div>
                <div class="modal-body">
                    {$content}
                </div>
                <div class="modal-footer">
                    {$actions}
                </div>
            </div>
        </div>
    HTML;
}
```

---

## :material-react: **React-like Patterns**

### Props Pattern

```php
<?php
function UserCard($props) {
    // Destructure props with defaults
    $name = $props['name'] ?? 'Anonymous';
    $avatar = $props['avatar'] ?? '/default-avatar.png';
    $bio = $props['bio'] ?? '';
    $verified = $props['verified'] ?? false;
    
    $verifiedBadge = $verified ? '<span class="verified">✓</span>' : '';
    
    return <<<HTML
        <div class="user-card">
            <img src="{$avatar}" alt="{$name}">
            <h3>{$name} {$verifiedBadge}</h3>
            <p>{$bio}</p>
        </div>
    HTML;
}

// Usage:
echo UserCard([
    'name' => 'Jane Smith',
    'avatar' => '/avatars/jane.jpg',
    'bio' => 'Full-stack developer and coffee enthusiast',
    'verified' => true
]);
```

### Children Pattern

```php
<?php
function Container($children, $className = 'container') {
    return <<<HTML
        <div class="{$className}">
            {$children}
        </div>
    HTML;
}

function Sidebar($children) {
    return <<<HTML
        <aside class="sidebar">
            {$children}
        </aside>
    HTML;
}

// Usage:
echo Container(
    Sidebar(NavigationMenu($menuItems)) . MainContent($pageContent)
);
```

---

## :gear: **Component Configuration**

### Setting Component Properties

```php
<?php
$component = new Component('Dashboard');

// Basic route
$component->route('/dashboard');

// Multiple routes
$component->route(['/dashboard', '/admin', '/panel']);

// HTTP methods
$component->method(['GET', 'POST']);

// Page title
$component->title('Admin Dashboard');

// Target element (where to render)
$component->targetID('main-content');

// Case sensitivity
$component->caseSensitive(true);
```

### Method Chaining

```php
<?php
$dashboard = (new Component('Dashboard'))
    ->route('/dashboard')
    ->method(['GET', 'POST'])
    ->title('Dashboard')
    ->targetID('app');

$app->attach($dashboard);
```

---

## :mag: **Best Practices**

### :white_check_mark: **Do's**

```php
<?php
// ✅ Keep components focused and single-purpose
function AlertMessage($message, $type) {
    return "<div class=\"alert alert-{$type}\">{$message}</div>";
}

// ✅ Use descriptive names
function ShoppingCartSummary($cart) { /* ... */ }

// ✅ Handle edge cases
function UserList($users) {
    if (empty($users)) {
        return '<p>No users found.</p>';
    }
    // ... render users
}

// ✅ Escape output for security
function DisplayComment($comment) {
    $safeContent = htmlspecialchars($comment['content']);
    return "<div class=\"comment\">{$safeContent}</div>";
}
```

### :x: **Avoid**

```php
<?php
// ❌ Don't make components too large
function MegaComponent() {
    // 200+ lines of HTML - split this up!
}

// ❌ Don't mix concerns
function UserProfileWithDatabaseLogic($userId) {
    $user = Database::find($userId); // Database logic doesn't belong here
    return "<div>{$user['name']}</div>";
}

// ❌ Don't forget to handle null/empty data
function ProductPrice($product) {
    return "<span>\${$product['price']}</span>"; // What if price is null?
}
```

---

## :books: **Next Steps**

Now that you understand components, explore:

- [**Props & Data**](props.md) — Passing data between components
- [**State Management**](state.md) — Making components reactive  
- [**Component Nesting**](../components/nesting.md) — Building complex UIs
- [**Class Components**](../components/classes.md) — Object-oriented approach

---

!!! tip "Component Philosophy"
    Think of components as **building blocks**. Each component should do one thing well, be easy to test, and be reusable across your application. When in doubt, create smaller components rather than larger ones.
