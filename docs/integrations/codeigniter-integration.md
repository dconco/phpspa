# Using phpSPA with CodeIgniter

## Introduction

CodeIgniter provides a simple yet powerful framework for rapid web development. When you need dynamic, real-time updates in specific sections without full page reloads, phpSPA integrates seamlessly with CodeIgniter's MVC architecture!

**Perfect Use Case: E-commerce Admin Panel**

Imagine you're building an e-commerce admin panel with multiple sections:

1. **Product Catalog** - Displays all products with filters
2. **Order Management** - Shows pending and completed orders
3. **Customer Analytics** - Real-time customer statistics

Instead of reloading the entire admin panel when switching between sections or updating product information, phpSPA allows you to update just the active section dynamically while keeping the navigation and sidebar intact. This creates a smooth, modern admin experience within your CodeIgniter application.

## Installation

Add phpSPA to your CodeIgniter project using Composer:

```bash
composer require dconco/phpspa
```

If you're not using Composer with CodeIgniter, you can download phpSPA manually and include it in your `application/third_party/` directory.

## Basic Setup

### 1. CodeIgniter Controller Setup

Create a new controller for your phpSPA application:

```php
<?php
// application/controllers/Admin.php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Load any models or libraries you need
        $this->load->model('Product_model');
        $this->load->model('Order_model');
        $this->load->library('session');

        // Include phpSPA
        require_once APPPATH . 'third_party/phpspa/vendor/autoload.php';
        // OR if using Composer globally:
        // require_once FCPATH . 'vendor/autoload.php';
    }

    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        // Define the layout
        function Layout() {
            return <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>E-commerce Admin Panel</title>
                    <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
                </head>
                <body>
                    <div id="root">__CONTENT__</div>
                </body>
                </html>
            HTML;
        }

        // Initialize phpSPA
        $app = new phpSPA\App('Layout');
        $app->defaultTargetID('root');

        // Pass CodeIgniter instance to components
        $GLOBALS['CI'] =& get_instance();

        // Attach components
        $app->attach(require APPPATH . 'phpspa_components/AdminLayoutComp.php');
        $app->attach(require APPPATH . 'phpspa_components/ProductComp.php');
        $app->attach(require APPPATH . 'phpspa_components/OrderComp.php');

        // Run the application
        $app->run();
    }
}
```

### 2. CodeIgniter Routes Configuration

Update your `application/config/routes.php` to handle phpSPA requests:

```php
<?php
// application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// Default CodeIgniter routes
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// phpSPA routes
$route['admin'] = 'admin/dashboard';
$route['admin/dashboard'] = 'admin/dashboard';
$route['admin/products'] = 'admin/dashboard';
$route['admin/orders'] = 'admin/dashboard';
$route['admin/analytics'] = 'admin/dashboard';
```

!!! note "Route Handling"
The `PHPSPA_GET` method is used by phpSPA for internal routing within CodeIgniter. All phpSPA routes point to the same controller method, which then handles the routing internally through phpSPA components.

### 3. Create Components Directory

Create the components directory in your CodeIgniter application:

```bash
mkdir -p application/phpspa_components
```

## Component Files

### 4. Admin Layout Component

Create `application/phpspa_components/AdminLayoutComp.php`:

```php
<?php
use phpSPA\Component;

// Include other components for usage
include_once 'ProductComp.php';
include_once 'OrderComp.php';

return (new Component(fn () => <<<HTML
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li>
                  <PhpSPA.Component.Link to="/admin/products">Products</PhpSPA.Component.Link>
                </li>
                <li>
                  <PhpSPA.Component.Link to="/admin/orders">Orders</PhpSPA.Component.Link>
                </li>
                <li>
                  <PhpSPA.Component.Link href="/admin/analytics">Analytics</PhpSPA.Component.Link>
                </li>
            </ul>
        </nav>

        <div class="admin-content">
            <div id="main-content">
                <div class="welcome-section">
                    <h3>Welcome to Admin Dashboard</h3>
                    <p>Select a section from the navigation to get started.</p>
                </div>
            </div>
        </div>

        <div class="admin-sidebar">
            <h4>Quick Stats</h4>
            <div class="stat-item">Products: 247</div>
            <div class="stat-item">Orders: 89</div>
            <div class="stat-item">Customers: 156</div>
        </div>
    </div>
HTML))
->route('/admin')
->method('GET')
->title('Admin Dashboard');
```

### 5. Product Component

Create `application/phpspa_components/ProductComp.php`:

```php
<?php

use phpSPA\Component;
use phpSPA\Http\Request;

function ProductComp(Request $request = new Request()) {
    // Access CodeIgniter instance
    $CI =& $GLOBALS['CI'];

    // Get products from CodeIgniter model
    $products = [];
    if (isset($CI->Product_model)) {
        $products = $CI->Product_model->get_all_products();
    }

    // Mock data for demonstration
    if (empty($products)) {
        $products = [
            ['id' => 1, 'name' => 'Laptop Pro', 'price' => 1299.99, 'stock' => 15],
            ['id' => 2, 'name' => 'Smartphone X', 'price' => 899.99, 'stock' => 23],
            ['id' => 3, 'name' => 'Wireless Headphones', 'price' => 199.99, 'stock' => 45],
            ['id' => 4, 'name' => 'Gaming Mouse', 'price' => 79.99, 'stock' => 67]
        ];
    }

    $productList = '';
    foreach ($products as $product) {
        $productList .= <<<HTML
            <div class="product-item" onclick="phpspa.navigate('/admin/products?id={$product['id']}')">
                <h4>{$product['name']}</h4>
                <p>Price: $<span>{$product['price']}</span></p>
                <p>Stock: {$product['stock']} units</p>
            </div>
        HTML;
    }

    return <<<HTML
        <div class="product-section">
            <div class="section-header">
                <h3>Product Management</h3>
                <button onclick="alert('Add New Product')">Add Product</button>
            </div>

            <div class="filter-bar">
                <input type="text" placeholder="Search products..." />
                <select>
                    <option>All Categories</option>
                    <option>Electronics</option>
                    <option>Accessories</option>
                </select>
            </div>

            <div class="product-grid">
                {$productList}
            </div>
        </div>
    HTML;
}

$request = new Request();

return (new Component('ProductComp'))
    ->route('/admin/products')
    ->method('POST')
    ->targetID('main-content')
    ->title('Product Management');
```

### 6. Order Component

Create `application/phpspa_components/OrderComp.php`:

```php
<?php

use phpSPA\Component;
use phpSPA\Http\Request;

function OrderComp(Request $request = new Request()) {
    // Access CodeIgniter instance
    $CI =& $GLOBALS['CI'];

    // Get orders from CodeIgniter model
    $orders = [];
    if (isset($CI->Order_model)) {
        $orders = $CI->Order_model->get_recent_orders();
    }

    // Mock data for demonstration
    if (empty($orders)) {
        $orders = [
            ['id' => 1001, 'customer' => 'John Doe', 'total' => 1599.98, 'status' => 'Pending'],
            ['id' => 1002, 'customer' => 'Jane Smith', 'total' => 899.99, 'status' => 'Shipped'],
            ['id' => 1003, 'customer' => 'Mike Johnson', 'total' => 279.98, 'status' => 'Delivered'],
            ['id' => 1004, 'customer' => 'Sarah Wilson', 'total' => 79.99, 'status' => 'Processing']
        ];
    }

    $orderList = '';
    foreach ($orders as $order) {
        $statusClass = strtolower($order['status']);
        $orderList .= <<<HTML
            <div class="order-item" onclick="phpspa.navigate('/admin/orders?id={$order['id']}')">
                <div class="order-info">
                    <h4>Order #{$order['id']}</h4>
                    <p>Customer: {$order['customer']}</p>
                    <p>Total: $<span>{$order['total']}</span></p>
                </div>
                <div class="order-status {$statusClass}">{$order['status']}</div>
            </div>
        HTML;
    }

    return <<<HTML
        <div class="order-section">
            <div class="section-header">
                <h3>Order Management</h3>
                <button onclick="alert('Export Orders')">Export</button>
            </div>

            <div class="filter-bar">
                <select>
                    <option>All Orders</option>
                    <option>Pending</option>
                    <option>Shipped</option>
                    <option>Delivered</option>
                </select>
                <input type="date" />
            </div>

            <div class="order-list">
                {$orderList}
            </div>
        </div>
    HTML;
}

$request = new phpSPA\Http\Request();

return (new Component('OrderComp'))
    ->route('/admin/orders')
    ->method('PUT')
    ->targetID('main-content')
    ->title('Order Management');
```

!!! note "Default Parameters"
Make sure to provide default values for function parameters in your components, since phpSPA handles parameter passing automatically based on the component context.

## CodeIgniter Integration Features

### Using CodeIgniter Models

Access your CodeIgniter models within phpSPA components:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    // Access CodeIgniter instance
    $CI =& $GLOBALS['CI'];

    // Use CodeIgniter models
    $CI->load->model('Product_model');
    $products = $CI->Product_model->get_products_with_category();

    // Use CodeIgniter libraries
    $CI->load->library('pagination');
    $CI->load->helper('url');

    // Access session data
    $user_id = $CI->session->userdata('admin_id');
    $user_name = $CI->session->userdata('admin_name');

    // Use CodeIgniter configuration
    $per_page = $CI->config->item('products_per_page');

    // Component logic here...
}
```

### Database Operations

Perform database operations using CodeIgniter's database class:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    $CI =& $GLOBALS['CI'];

    // Handle form submissions
    if ($request->method() === 'POST') {
        $product_data = [
            'name' => $request->post('name'),
            'price' => $request->post('price'),
            'description' => $request->post('description'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert using CodeIgniter's database
        $CI->db->insert('products', $product_data);

        // Or using a model
        $CI->Product_model->create_product($product_data);
    }

    // Get products with CodeIgniter Query Builder
    $products = $CI->db->select('*')
                      ->from('products')
                      ->where('status', 'active')
                      ->order_by('created_at', 'DESC')
                      ->limit(10)
                      ->get()
                      ->result_array();

    // Component rendering logic...
}
```

### Form Validation

Use CodeIgniter's form validation within components:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    $CI =& $GLOBALS['CI'];

    $validation_errors = '';

    if ($request->method() === 'POST') {
        // Load form validation library
        $CI->load->library('form_validation');

        // Set validation rules
        $CI->form_validation->set_rules('name', 'Product Name', 'required|min_length[3]');
        $CI->form_validation->set_rules('price', 'Price', 'required|decimal');
        $CI->form_validation->set_rules('stock', 'Stock', 'required|integer');

        if ($CI->form_validation->run()) {
            // Validation passed - save product
            $product_data = [
                'name' => $CI->input->post('name'),
                'price' => $CI->input->post('price'),
                'stock' => $CI->input->post('stock')
            ];

            $CI->Product_model->create_product($product_data);

            return <<<HTML
                <div class="success-message">
                    Product added successfully!
                </div>
            HTML;
        } else {
            // Validation failed - show errors
            $validation_errors = validation_errors('<div class="error">', '</div>');
        }
    }

    return <<<HTML
        <div class="product-form">
            <h3>Add New Product</h3>
            {$validation_errors}
            <form method="POST">
                <input type="text" name="name" placeholder="Product Name" required />
                <input type="number" name="price" step="0.01" placeholder="Price" required />
                <input type="number" name="stock" placeholder="Stock Quantity" required />
                <button type="submit">Add Product</button>
            </form>
        </div>
    HTML;
}
```

## Security Integration

### Authentication Check

Integrate with CodeIgniter's authentication system:

```php
<?php
// application/phpspa_components/AdminLayoutComp.php

return (new Component(fn () => {
    $CI =& $GLOBALS['CI'];

    // Check if user is logged in
    if (!$CI->session->userdata('admin_logged_in')) {
        return <<<HTML
            <div class="login-required">
                <h3>Access Denied</h3>
                <p>Please log in to access the admin panel.</p>
                <PhpSPA.Component.Link to="/login" children="Login" />
            </div>
        HTML;
    }

    $admin_name = $CI->session->userdata('admin_name');

    return <<<HTML
        <div class="admin-header">
            <h2>Welcome, {$admin_name}</h2>
            <PhpSPA.Component.Link to="/logout">Logout</PhpSPA.Component.Link>
        </div>
        <div class="admin-content">
            <!-- Admin content here -->
        </div>
    HTML;
}))
->route('/admin')
->method('GET');
```

### CSRF Protection

Use CodeIgniter's CSRF protection in forms:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    $CI =& $GLOBALS['CI'];

    // Generate CSRF token
    $csrf_name = $CI->security->get_csrf_token_name();
    $csrf_hash = $CI->security->get_csrf_hash();

    return <<<HTML
        <form method="POST">
            <input type="hidden" name="{$csrf_name}" value="{$csrf_hash}" />
            <input type="text" name="product_name" required />
            <button type="submit">Add Product</button>
        </form>
    HTML;
}
```

## Configuration and Helpers

### Using CodeIgniter Configuration

Access configuration values in your components:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    $CI =& $GLOBALS['CI'];

    // Load configuration
    $CI->config->load('app_config');

    // Get configuration values
    $upload_path = $CI->config->item('upload_path');
    $max_file_size = $CI->config->item('max_upload_size');
    $allowed_types = $CI->config->item('allowed_file_types');

    // Use in component logic...
}
```

### Using CodeIgniter Helpers

Load and use CodeIgniter helpers:

```php
<?php
// application/phpspa_components/ProductComp.php

function ProductComp(Request $request = new Request()) {
    $CI =& $GLOBALS['CI'];

    // Load helpers
    $CI->load->helper(['url', 'form', 'date']);

    // Use helper functions
    $base_url = base_url();
    $current_time = now();
    $formatted_date = mdate('%Y-%m-%d', $current_time);

    return <<<HTML
        <div class="product-info">
            <p>Base URL: {$base_url}</p>
            <p>Current Date: {$formatted_date}</p>
        </div>
    HTML;
}
```

## Best Practices

1. **File Organization**: Keep phpSPA components in `application/phpspa_components/` to follow CodeIgniter's structure.

2. **CodeIgniter Integration**: Always access the CodeIgniter instance through `$GLOBALS['CI']` in your components.

3. **Route Handling**: Use the same controller method for all phpSPA routes and let phpSPA handle internal routing.

4. **Method Matching**: Each component route uses its own normal HTTP method (GET, POST, PUT, DELETE) for proper REST API design.

5. **Security**: Leverage CodeIgniter's built-in security features like CSRF protection and input filtering.

6. **Database Operations**: Use CodeIgniter's Query Builder or models for database operations within components.

7. **Error Handling**: Implement proper error handling using CodeIgniter's error handling mechanisms.

## Deployment Considerations

### .htaccess Configuration

Ensure your `.htaccess` file supports phpSPA routing:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### Production Settings

Update your `application/config/config.php` for production:

```php
// Enable CSRF protection
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'csrf_cookie';

// Set base URL
$config['base_url'] = 'https://yourdomain.com/';

// Enable compression
$config['compress_output'] = TRUE;
```

## Next Steps

Now you have a fully functional e-commerce admin panel that combines CodeIgniter's simplicity with phpSPA's dynamic capabilities! The navigation and sidebar remain static while the main content area updates dynamically based on user interactions.

You can extend this by:

-  Integrating with CodeIgniter's email library for notifications
-  Adding CodeIgniter's upload library for file handling
-  Implementing CodeIgniter's caching for improved performance
-  Using CodeIgniter's logging system for debugging
-  Adding CodeIgniter's encryption library for sensitive data

Happy coding with CodeIgniter + phpSPA! 🚀
