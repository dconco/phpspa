# 📦 Installation Guide

Get phpSPA up and running in your environment. Choose the installation method that best fits your project setup and development workflow.

!!! info "Requirements"
    
    - **PHP 8.2+** with standard extensions
    - **Web server** (Apache, Nginx, or PHP built-in)
    - **Modern browser** with JavaScript ES6+ support
    - **Composer** (recommended for dependency management)

---

## 🚀 Installation Methods

### Method 1: Composer (Recommended)

The easiest and most maintainable way to install phpSPA:

```bash
# Create new project directory
mkdir my-phpspa-app
cd my-phpspa-app

# Install phpSPA via Composer
composer require dconco/phpspa

# Create basic structure
mkdir components assets
touch index.php Layout.php
```

**Include in your PHP files:**

```php
<?php
require_once 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Component;
use function phpSPA\Component\createState;
```

### Method 2: Template Project (Fastest)

Clone a complete, working example to get started immediately:

```bash
# Clone the template project
git clone https://github.com/mrepol742/phpspa-example my-phpspa-app
cd my-phpspa-app

# Install dependencies
composer install

# Start development server
composer start
```

**What you get:**
- ✅ Complete project structure
- ✅ Example components and routing
- ✅ Development scripts
- ✅ Production configuration

### Method 3: Manual Installation

For projects that don't use Composer or need custom setup:

```bash
# Download phpSPA
git clone https://github.com/dconco/phpspa.git
cd phpspa

# Copy core files to your project
cp -r app/ /path/to/your/project/phpspa/
```

**Manual include:**

```php
<?php
// Include core files manually
require_once 'phpspa/app/client/App.php';
require_once 'phpspa/app/client/Component.php';

use phpSPA\App;
use phpSPA\Component;
```

### Method 4: CDN Only (Client-Side)

If you only need the JavaScript engine for integration with existing PHP:

```html
<!-- Include phpSPA JavaScript engine -->
<script src="https://unpkg.com/phpspa-js"></script>

<!-- Or specific version -->
<script src="https://unpkg.com/phpspa-js@1.1.7"></script>
```

---

## 🔧 Setup & Configuration

### Basic Project Structure

After installation, organize your project like this:

```
my-phpspa-app/
├── index.php              # Application entry point
├── Layout.php             # Base HTML template
├── components/            # Component functions
│   ├── HomePage.php
│   ├── UserProfile.php
│   └── ContactForm.php
├── assets/               # Static files
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── images/
├── config/              # Configuration files
│   └── app.php
├── vendor/              # Composer dependencies (if using)
└── .htaccess           # Apache rewrite rules (optional)
```

### Environment Detection

phpSPA automatically detects your environment for optimal configuration:

| Environment     | Auto-Detected When                           | Compression | Debug Mode |
| --------------- | -------------------------------------------- | ----------- | ---------- |
| **Development** | `localhost`, `127.0.0.1`, `*.local`, `*.dev` | None        | Enabled    |
| **Staging**     | `staging.*`, `test.*` subdomains             | Basic       | Partial    |
| **Production**  | All other domains                            | Aggressive  | Disabled   |

Override environment detection:

```php
// Force specific environment
$app = new App('Layout');
$app->compressionEnvironment('production'); // or 'development', 'staging'
```

### Web Server Configuration

#### Apache (.htaccess)

```apache
# Enable mod_rewrite
RewriteEngine On

# Handle phpSPA routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Enable compression (if supported)
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/html;
    index index.php;

    # Handle phpSPA routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Enable gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/css application/javascript application/json text/html text/plain;

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### PHP Built-in Server (Development)

```bash
# Start development server
php -S localhost:8000

# With custom router (recommended)
php -S localhost:8000 router.php
```

**router.php for development:**

```php
<?php
// Development router for PHP built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
    return false;
}

// Route everything else to index.php
require_once 'index.php';
```

---

## 🧪 Verification & Testing

### Test Your Installation

Create a simple test file to verify phpSPA is working:

```php title="test.php"
<?php
require_once 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Component;
use function phpSPA\Component\createState;

// Test layout
function TestLayout() {
    return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>phpSPA Test</title>
        </head>
        <body>
            <div id="app"><!-- Content here --></div>
            <script src="https://unpkg.com/phpspa-js"></script>
        </body>
        </html>
    HTML;
}

// Test component
function TestComponent() {
    $message = createState('message', 'Hello, phpSPA!');
    
    return <<<HTML
        <div>
            <h1>{$message}</h1>
            <button onclick="phpspa.setState('message', 'phpSPA is working! 🎉')">
                Test State Update
            </button>
        </div>
    HTML;
}

// Test app
$app = (new App('TestLayout'))
    ->attach(
        (new Component('TestComponent'))
            ->route('/')
            ->title('phpSPA Test')
    )
    ->defaultTargetID('app')
    ->run();
```

**Run the test:**

```bash
php -S localhost:8000 test.php
```

Visit `http://localhost:8000` and click the button. If the text updates, phpSPA is working correctly!

### Check Extensions

Verify optional extensions for enhanced features:

```php
<?php
// Check gzip compression support (optional for better performance)
use phpSPA\Compression\Compressor;

if (Compressor::supportsGzip()) {
    echo "✅ Gzip compression supported\n";
} else {
    echo "⚠️ Gzip compression not available\n";
    echo "   Enable zlib extension or zlib.output_compression in php.ini for better performance\n";
}

// Check fileinfo extension (optional for file handling)
if (extension_loaded('fileinfo')) {
    echo "✅ fileinfo extension installed\n";
} else {
    echo "⚠️ fileinfo extension not installed (needed for some file operations)\n";
}
```

---

## ⚙️ Configuration Options

### Basic Configuration

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = new App('Layout');

// Basic configuration
$app->defaultTargetID('app')           // Default DOM target
    ->defaultToCaseSensitive()         // Route case sensitivity
    ->compression(Compressor::LEVEL_AUTO)  // Auto compression
    ->cors()                           // Enable CORS
    ->run();
```

### Advanced Configuration

```php
<?php
// Custom configuration based on phpSPA features
class AppConfig {
    public static function create() {
        $app = new App('Layout');
        
        // Compression settings (from phpSPA compression docs)
        $app->compression(Compressor::LEVEL_EXTREME, true)
            ->compressionEnvironment(Compressor::ENV_PRODUCTION);
        
        // CORS settings (from phpSPA core features)
        $app->cors();
        
        return $app;
    }
}

// Use configuration
$app = AppConfig::create();
```

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: "Class not found" errors

**Solution:**
```bash
# Regenerate Composer autoloader
composer dump-autoload
```

#### Issue: JavaScript not working

**Checklist:**
- ✅ Include phpSPA JS: `<script src="https://unpkg.com/phpspa-js"></script>`
- ✅ Proper target ID: `defaultTargetID('app')` matches `<div id="app">`
- ✅ Browser console shows no errors
- ✅ Server returns JSON for AJAX requests

#### Issue: Routes not working

**Solutions:**
```apache
# Apache: Enable mod_rewrite
a2enmod rewrite

# Check .htaccess file exists and is readable
ls -la .htaccess
```

```php
// Debug routing
$app->attach(
    (new Component(function() {
        return "<h1>Debug: Route works!</h1>";
    }))->route('/debug')
);
```

#### Issue: Compression not working

**Check:**
```php
<?php
// Test compression support (from phpSPA compression docs)
use phpSPA\Compression\Compressor;

if (Compressor::supportsGzip()) {
    echo "✅ Gzip compression supported\n";
} else {
    echo "❌ Enable zlib extension or zlib.output_compression in php.ini\n";
}
```

### Debug Mode

Enable debug information:

```php
<?php
// Debug configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// phpSPA debug mode
$app = new App('Layout', true); // true enables debug mode
```

---

## 📚 Next Steps

After successful installation:

<div class="buttons" markdown>
[Build Your First Component](first-component.md){ .md-button .md-button--primary }
[Quick Start Tutorial](quick-start.md){ .md-button }
[Component Guide](../components/index.md){ .md-button }
[Routing Guide](../routing/index.md){ .md-button }
</div>

---

## 💡 Installation Tips

!!! tip "Development Workflow"
    
    1. **Use Template Project** for fastest start
    2. **Enable debug mode** during development
    3. **Use PHP built-in server** for quick testing
    4. **Set up auto-reload** for efficient development

!!! info "Production Deployment"
    
    1. **Use Composer** for dependency management
    2. **Enable compression** for performance
    3. **Configure web server** properly
    4. **Set up caching** and CDN

!!! success "You're Ready!"
    
    phpSPA is now installed and configured. Time to build something amazing!
