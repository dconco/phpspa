# :compass: Routing

Build **multi-page applications** with smooth navigation. phpSPA's routing system enables true SPA behavior while maintaining SEO-friendly URLs.

---

## :globe_with_meridians: **Core Concepts**

### Single Page Application (SPA)
phpSPA renders components dynamically without full page reloads, providing a smooth user experience similar to native mobile apps.

### URL-Component Mapping
Each route maps to a specific component, allowing users to bookmark pages and navigate with browser back/forward buttons.

---

## :straight_ruler: **Basic Routing**

### Simple Routes

```php title="Basic Route Registration"
<?php
use phpSPA\{App, Component};

$app = new App('layout');

// Homepage
$home = new Component('HomePage');
$home->route('/');

// About page  
$about = new Component('AboutPage');
$about->route('/about');

// Contact page
$contact = new Component('ContactPage');
$contact->route('/contact');

$app->attach($home)
    ->attach($about)
    ->attach($contact)
    ->run();
```

### Route Arrays
Map multiple URLs to the same component:

```php title="Multiple Routes"
<?php
$profile = new Component('UserProfile');
$profile->route([
    '/profile',
    '/user',
    '/me',
    '/account'
]);
```

---

## :gear: **Route Parameters**

### Dynamic Segments
Capture URL parameters for dynamic content:

```php title="Dynamic Routes"
<?php
function UserProfile($params = []) {
    $userId = $params['id'] ?? 'unknown';
    $username = $params['username'] ?? 'Guest';
    
    return <<<HTML
        <div class="profile">
            <h1>User Profile</h1>
            <p>ID: {$userId}</p>
            <p>Username: {$username}</p>
        </div>
    HTML;
}

// Register with parameter patterns
$profile = new Component('UserProfile');
$profile->route('/user/{id}/profile/{username}');
```

### Parameter Types
Enforce parameter validation with type constraints:

```php title="Typed Parameters"
<?php
// Integer parameters
$product = new Component('ProductPage');
$product->route('/product/{id:int}');

// String parameters  
$category = new Component('CategoryPage');
$category->route('/category/{name:string}');

// Multiple types
$search = new Component('SearchResults');
$search->route('/search/{query:string}/page/{page:int}');
```

**Supported Types:**
- `int` - Integer numbers
- `string` - Text strings
- `float` - Decimal numbers
- `bool` - Boolean values
- `alpha` - Alphabetic characters only
- `alnum` - Alphanumeric characters

---

## :lock: **HTTP Methods**

### Method Restrictions
Restrict routes to specific HTTP methods:

```php title="HTTP Method Filtering"
<?php
// GET only (default)
$blog = new Component('BlogList');
$blog->route('/blog')->method('GET');

// POST for form submissions
$contact = new Component('ContactForm');
$contact->route('/contact')->method('POST');

// Multiple methods
$api = new Component('ApiEndpoint');
$api->route('/api/users')->method(['GET', 'POST', 'PUT']);
```

### RESTful Routes

```php title="REST API Routes"
<?php
// GET /api/users - List all users
$userList = new Component('UserList');
$userList->route('/api/users')->method('GET');

// POST /api/users - Create user
$userCreate = new Component('UserCreate');
$userCreate->route('/api/users')->method('POST');

// GET /api/users/{id} - Get specific user
$userShow = new Component('UserShow');
$userShow->route('/api/users/{id:int}')->method('GET');

// PUT /api/users/{id} - Update user
$userUpdate = new Component('UserUpdate');
$userUpdate->route('/api/users/{id:int}')->method('PUT');

// DELETE /api/users/{id} - Delete user
$userDelete = new Component('UserDelete');
$userDelete->route('/api/users/{id:int}')->method('DELETE');
```

---

## :world_map: **Advanced Routing**

### Route Groups
Organize related routes with common prefixes:

```php title="Grouped Routes"
<?php
// Admin routes with /admin prefix
$adminRoutes = [
    new Component('AdminDashboard') => '/admin/dashboard',
    new Component('AdminUsers') => '/admin/users',
    new Component('AdminSettings') => '/admin/settings'
];

foreach ($adminRoutes as $component => $route) {
    $component->route($route)->method('GET');
    $app->attach($component);
}
```

### Conditional Routes
Route based on conditions:

```php title="Conditional Routing"
<?php
function AdminPanel($params = []) {
    // Check if user is admin
    if (!isUserAdmin()) {
        return '<h1>Access Denied</h1>';
    }
    
    return '<h1>Admin Dashboard</h1>';
}

$admin = new Component('AdminPanel');
$admin->route('/admin')->method('GET');
```

### Wildcard Routes
Catch-all routes for error handling:

```php title="404 Error Handling"
<?php
function NotFound() {
    return <<<HTML
        <div class="error-page">
            <h1>404 - Page Not Found</h1>
            <p>The page you're looking for doesn't exist.</p>
            <a href="/">Go Home</a>
        </div>
    HTML;
}

// Register as catch-all (register last)
$notFound = new Component('NotFound');
$notFound->route('*');
```

---

## :link: **Navigation**

### Link Component
Create navigation links that work with SPA routing:

```php title="Navigation Links"
<?php
use function Component\Link;

function Navigation() {
    $homeLink = Link('Home', '/');
    $aboutLink = Link('About', '/about');
    $contactLink = Link('Contact', '/contact');
    
    return <<<HTML
        <nav class="main-nav">
            {$homeLink}
            {$aboutLink}
            {$contactLink}
        </nav>
    HTML;
}
```

### Programmatic Navigation
Navigate from PHP code:

```php title="Programmatic Navigation"
<?php
use function Component\Navigate;
use phpSPA\Core\Helper\Enums\NavigateState;

function LoginForm() {
    // Handle form submission
    if ($_POST['login']) {
        // Validate credentials
        if (validateLogin($_POST['username'], $_POST['password'])) {
            // Redirect to dashboard
            $redirect = Navigate('/dashboard', NavigateState::REPLACE);
            return $redirect;
        }
    }
    
    return <<<HTML
        <form method="post">
            <input type="text" name="username" placeholder="Username">
            <input type="password" name="password" placeholder="Password">
            <button type="submit" name="login">Login</button>
        </form>
    HTML;
}
```

---

## :arrows_counterclockwise: **Route Matching**

### Case Sensitivity
Control route case sensitivity:

```php title="Case Sensitivity"
<?php
// Case sensitive (default)
$app = new App('layout');

// Make routes case insensitive
$app->defaultToCaseSensitive(false);

// Per-component case sensitivity
$component = new Component('HomePage');
$component->route('/HOME')->caseSensitive(false);
```

### Priority Order
Routes are matched in registration order:

```php title="Route Priority"
<?php
// More specific routes first
$userProfile = new Component('UserProfile');
$userProfile->route('/user/profile');

$userPosts = new Component('UserPosts');
$userPosts->route('/user/posts');

// Generic routes last
$userGeneric = new Component('UserGeneric');
$userGeneric->route('/user/{action}');
```

---

## :mag: **Route Debugging**

### Debug Mode
Enable route debugging to see matching process:

```php title="Debug Routes"
<?php
// Enable debug mode
$app = new App('layout');
$app->debug(true);

// Or check routes programmatically
function debugRoutes() {
    $currentRoute = $_SERVER['REQUEST_URI'];
    error_log("Current route: $currentRoute");
    
    // Log all registered routes
    foreach ($app->getRoutes() as $route) {
        error_log("Registered: {$route->getPattern()}");
    }
}
```

---

## :bulb: **Best Practices**

!!! tip "Routing Tips"

    **📏 Keep routes clean**
    Use descriptive, hyphenated URLs: `/blog-posts` instead of `/blogPosts`

    **🏷️ Use typed parameters**
    Always specify parameter types to prevent invalid data

    **🔒 Validate permissions**
    Check user permissions inside components for protected routes

    **📱 Mobile-first**
    Design routes that work well on all devices

### SEO-Friendly Routes

```php title="SEO Best Practices"
<?php
// Good: descriptive, hyphenated
$blog = new Component('BlogPost');
$blog->route('/blog/post/{slug:string}');

// Good: hierarchical structure  
$category = new Component('ProductCategory');
$category->route('/shop/{category:string}/{subcategory:string}');

// Bad: unclear purpose
$page = new Component('GenericPage');
$page->route('/p/{id:int}');
```

---

## :question: **Common Issues**

!!! warning "Troubleshooting"

    **Routes not working?**
    
    1. Check web server configuration (Apache needs `.htaccess`)
    2. Verify route patterns don't have typos
    3. Ensure more specific routes come before generic ones
    
    **Parameters not passed?**
    
    1. Check parameter names match route pattern
    2. Verify parameter types are correct
    3. Make sure component function accepts `$params` array

### Web Server Setup

=== ":material-apache: Apache (.htaccess)"

    ```apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
    ```

=== ":material-nginx: Nginx"

    ```nginx
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```

=== ":material-server: PHP Built-in Server"

    ```bash
    php -S localhost:8000 -t public
    ```

---

!!! success "You're a Routing Pro!"
    You now understand phpSPA's routing system. Next, learn about [State Management →](state.md) to build interactive components that respond to user actions.
