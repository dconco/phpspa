# Framework Integration Guide

## Transform Your Existing PHP Projects with phpSPA

phpSPA is designed to seamlessly integrate with your existing PHP applications and popular frameworks, bringing modern SPA functionality without the need for complete rewrites or complex JavaScript setups.

## Why Integrate phpSPA?

### 🔄 **Partial Page Updates**

Instead of full page reloads, update only specific sections of your application. Perfect for:

-  **Chat applications** - Update message areas without refreshing user lists
-  **Dashboard widgets** - Refresh data tables while keeping navigation intact
-  **E-commerce filters** - Update product listings without losing filter states
-  **Admin panels** - Dynamic content management without page jumps

### ⚡ **Enhanced User Experience**

-  **Instant responses** - No more waiting for full page reloads
-  **Smooth transitions** - Maintain user context and scroll positions
-  **Reduced bandwidth** - Only transfer the content that changes
-  **Mobile-friendly** - Faster interactions on slower connections

### 🛠️ **Developer Benefits**

-  **Familiar PHP syntax** - No need to learn complex JavaScript frameworks
-  **Framework agnostic** - Works with Laravel, Symfony, CodeIgniter, and more
-  **SEO-friendly** - Server-side rendering ensures search engine compatibility
-  **Progressive enhancement** - Gracefully degrades without JavaScript

## Integration Scenarios

### 🏗️ **Existing Projects**

Transform specific sections of your current PHP application into dynamic components:

```php
// Your existing page.php
<?php include 'header.php'; ?>

<div class="dashboard">
    <div class="sidebar">
        <!-- Static sidebar content -->
    </div>

    <div id="dynamic-content">
        <?php
        // Replace this section with phpSPA for dynamic updates
        use phpSPA\App;

        $app = new App('DashboardLayout');
        $app->attach(require 'components/StatsWidget.php');
        $app->attach(require 'components/DataTable.php');
        $app->run();
        ?>
    </div>
</div>

<?php include 'footer.php'; ?>
```

### 🚀 **Framework Integration**

Enhance your framework applications with dynamic components:

#### **Laravel**

Perfect for adding real-time features to your Laravel applications without abandoning Blade templates or Eloquent models.

#### **Symfony**

Integrate with Twig templates and Doctrine while adding SPA-like behavior to specific routes.

#### **CodeIgniter**

Modernize your CodeIgniter applications with dynamic content updates while keeping the familiar MVC structure.

#### **Pure PHP**

Add sophisticated SPA functionality to any PHP application, regardless of architecture.

## Common Use Cases

### 💬 **Real-time Communication**

-  **Chat systems** - Update conversations without page refreshes
-  **Comment sections** - Dynamic comment loading and posting
-  **Notification centers** - Live notification updates

### 📊 **Data Management**

-  **Admin dashboards** - Dynamic charts and statistics
-  **Data tables** - Sorting, filtering, and pagination without reloads
-  **Form wizards** - Multi-step forms with smooth transitions

### 🛒 **E-commerce Features**

-  **Product catalogs** - Dynamic filtering and searching
-  **Shopping carts** - Real-time cart updates
-  **Checkout processes** - Seamless multi-step checkout

### 📱 **Interactive Interfaces**

-  **Tab systems** - Content switching without page loads
-  **Modal dialogs** - Dynamic content loading
-  **Infinite scroll** - Progressive content loading

## Integration Approaches

### 🎯 **Targeted Integration**

Start small by converting specific sections:

-  Identify high-interaction areas in your application
-  Replace traditional form submissions with phpSPA components
-  Gradually expand to more sections as you become comfortable

### 🏢 **Framework-Specific Integration**

Leverage your framework's strengths:

-  Use existing models, services, and business logic
-  Maintain your current authentication and authorization systems
-  Keep your established routing and middleware patterns

### 🔧 **Hybrid Approach**

Combine traditional server-side rendering with dynamic sections:

-  Static content remains server-rendered for SEO
-  Interactive elements become phpSPA components
-  Best of both worlds - performance and user experience

## Getting Started

Choose your integration path:

### [🟢 Laravel Integration →](./laravel-integration.md)

**Most Popular Choice**

-  Seamless Eloquent integration
-  Works with existing Blade templates
-  Maintains Laravel's routing system
-  Perfect for modern Laravel applications

### [🔵 Symfony Integration →](./symfony-integration.md)

**Enterprise Ready**

-  Compatible with Symfony's service container
-  Works alongside Twig templates
-  Maintains Symfony's security features
-  Ideal for complex enterprise applications

### [🟡 CodeIgniter Integration →](./codeigniter-integration.md)

**Legacy Modernization**

-  Easy integration with CI's MVC pattern
-  Maintains existing database connections
-  Works with CI's libraries and helpers
-  Perfect for modernizing legacy applications

### [🟠 Pure PHP Integration →](./pure-php-integration.md)

**Maximum Flexibility**

-  No framework dependencies
-  Works with any PHP application
-  Custom implementation options
-  Complete control over integration


## What You'll Learn

Each integration guide covers:

-  ✅ **Installation and setup** specific to your framework
-  ✅ **Component creation** following framework conventions
-  ✅ **Routing configuration** to handle phpSPA requests
-  ✅ **State management** within your framework's context
-  ✅ **Best practices** for optimal performance and maintainability
-  ✅ **Real-world examples** with complete working code
-  ✅ **Troubleshooting** common integration issues

## Ready to Transform Your Application?

Select your framework or project type above to begin integrating phpSPA and start building modern, dynamic PHP applications today!

---

_phpSPA - Bringing React-like experiences to PHP, one component at a time._ 🚀
