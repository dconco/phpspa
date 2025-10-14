# Configuration: Handling CORS 🌍

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

If you intend for your PhpSPA application to be used as an API for a frontend hosted on a different domain, you'll need to configure Cross-Origin Resource Sharing (CORS).

!!! info "Simple CORS Configuration"
    PhpSPA makes this incredibly simple with the `->cors()` method, which you can chain directly onto your `$app` instance.

## Enabling with Defaults

To enable CORS with a secure and sensible set of default headers, simply call the `cors()` method with no arguments. This is often all you need.

```php
<?php
use PhpSPA\App;

$app = new App($layout);

// Enable CORS with default settings
$app->cors();

// ... attach components, etc.

$app->run();
```

!!! tip "Quick Setup"
    The default configuration is suitable for most use cases and includes secure CORS headers.

## Customizing CORS Settings

For more specific control, you can pass an associative array to the `cors()` method to define exactly which origins, methods, and headers are allowed.

```php
<?php
use PhpSPA\App;

$app = new App($layout);

// Enable CORS with custom settings
$app->cors([
    'allow_origins' => ['https://my-frontend-app.com', 'https://staging.my-app.com'],
    'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allow_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
    'allow_credentials' => true,
    'max_age' => 86400, // Cache preflight requests for 24 hours
]);

// ...

$app->run();
```

!!! success "Fine-Grained Control"
    This gives you fine-grained control to securely manage how your PhpSPA backend interacts with other web applications.

### Configuration Options

<div class="grid cards" markdown>

-   :material-web: **allow_origins**

    ---

    Array of allowed domain origins
    
    Example: `['https://example.com']`

-   :material-api: **allow_methods**

    ---

    HTTP methods to allow
    
    Example: `['GET', 'POST', 'PUT']`

-   :material-format-header-pound: **allow_headers**

    ---

    Headers that can be used in requests
    
    Example: `['Authorization', 'Content-Type']`

-   :material-lock: **allow_credentials**

    ---

    Allow cookies and authentication
    
    Default: `false`

-   :material-clock-outline: **max_age**

    ---

    Preflight cache duration in seconds
    
    Default: `3600` (1 hour)

</div>
