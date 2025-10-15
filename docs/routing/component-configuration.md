# Component Configuration

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

PhpSPA provides several powerful methods to configure how your components behave. These methods can be chained directly onto your component instances to customize routing, rendering, titles, and more.

!!! info "Method Chaining"
    All component configuration methods return the component instance, allowing you to chain multiple configurations together.

## Setting the Page Title

Use the `->title()` method to set a dynamic page title for a component. This updates the browser's title bar when the component is loaded.

```php
<?php
use PhpSPA\Component;

$homePage = new Component(function() {
    return '<h1>Welcome Home!</h1>';
});

$homePage
    ->route('/')
    ->title('Home - My App');
```

!!! tip "Dynamic Titles"
    Each component can have its own unique title, making your SPA feel like a traditional multi-page application.

## Specifying HTTP Methods

By default, components accept both `GET` and `POST` requests. Use the `->method()` method to restrict which HTTP methods are allowed.

```php
<?php
use PhpSPA\Component;

// Only accept GET requests
$viewPage = new Component(fn() => '<div>Read-only content</div>');
$viewPage
    ->route('/view')
    ->method('GET');

// Only accept POST requests (for form submissions)
$submitForm = new Component(function() {
    return '<div>Form submitted!</div>';
});
$submitForm
    ->route('/submit')
    ->method('POST');

// Accept multiple methods
$apiEndpoint = new Component(fn() => '<div>API Response</div>');
$apiEndpoint
    ->route('/api/data')
    ->method('GET|POST|PUT|DELETE');
```

!!! example "Use Cases"
    - **GET only**: Read-only pages, documentation
    - **POST only**: Form submissions, data creation
    - **Multiple methods**: API endpoints, flexible handlers

## Setting the Target Render Element

Components can render into specific elements in your layout using the `->targetID()` method. This is useful for rendering different components in different areas of your page.

```php
<?php
use PhpSPA\Component;

// Render in the main content area
$mainContent = new Component(fn() => '<div>Main Content</div>');
$mainContent
    ->route('/dashboard')
    ->targetID('main-content');

// Render in a sidebar
$sidebar = new Component(fn() => '<div>Sidebar Widget</div>');
$sidebar
    ->route('/sidebar')
    ->targetID('sidebar');
```

**In your layout:**

```html
<!DOCTYPE html>
<html>
<body>
    <div id="main-content"></div>
    <aside id="sidebar"></aside>
</body>
</html>
```

!!! info "Default Target ID"
    Set a default target ID for all components using `$app->defaultTargetID("app")`. Individual components can override this with their own `->targetID()`.

## Route Case Sensitivity

By default, routes are case-insensitive. You can control this behavior per component or globally.

### Per-Component Case Sensitivity

=== "Case Sensitive"

    Make a specific route case-sensitive:

    ```php
    <?php
    $component = new Component(fn() => '<div>Content</div>');
    $component
        ->route('/AboutUs')
        ->caseSensitive();
    
    // Only matches: /AboutUs
    // Does NOT match: /aboutus, /ABOUTUS
    ```

=== "Case Insensitive"

    Explicitly make a route case-insensitive:

    ```php
    <?php
    $component = new Component(fn() => '<div>Content</div>');
    $component
        ->route('/contact')
        ->caseInsensitive();
    
    // Matches: /contact, /Contact, /CONTACT, /CoNtAcT
    ```

!!! tip "When to Use Case Sensitivity"
    - **Case sensitive**: API endpoints, specific resource identifiers
    - **Case insensitive** (default): User-facing pages for better accessibility

### Global Case Sensitivity

Make all routes case-sensitive by default:

```php
<?php
use PhpSPA\App;

$app = new App($layout);

// Make all routes case-sensitive by default
$app->defaultToCaseSensitive();

// Individual components can still override this
$flexibleComponent = new Component(fn() => '<div>Flexible</div>');
$flexibleComponent
    ->route('/flexible')
    ->caseInsensitive(); // This one is still case-insensitive
```

!!! success "Best Practice"
    For consistency, set a global default and only override it for specific components that need different behavior.

## Complete Configuration Example

Here's an example using all configuration methods together:

```php
<?php
use PhpSPA\App;
use PhpSPA\Component;

$app = new App($layout);

// Set global defaults
$app->defaultTargetID('app');
$app->defaultToCaseSensitive();

// Configure a fully customized component
$userProfile = new Component(function() {
    return <<<HTML
        <div class="profile">
            <h1>User Profile</h1>
            <p>Welcome to your profile page!</p>
        </div>
    HTML;
});

$userProfile
    ->route('/user/profile')
    ->title('My Profile - UserApp')
    ->method('GET|POST')
    ->targetID('main-content')
    ->caseInsensitive();

$app->attach($userProfile);
$app->run();
```

<div class="grid cards" markdown>

-   :material-format-title: **title(string $title)**

    ---

    Sets the page title shown in the browser tab
    
    Example: `->title('Dashboard')`

-   :material-api: **method(string $method)**

    ---

    Specifies allowed HTTP methods
    
    Default: `'GET|POST'`

-   :material-target: **targetID(string $id)**

    ---

    Sets which element the component renders into
    
    Example: `->targetID('app')`

-   :material-format-letter-case: **caseSensitive()**

    ---

    Makes the route case-sensitive
    
    Only exact case matches work

-   :material-format-letter-case-lower: **caseInsensitive()**

    ---

    Makes the route case-insensitive
    
    Default behavior for routes

</div>
