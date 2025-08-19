# :package: Installation

phpSPA is designed to be **easy to install** and **quick to set up**. Choose the method that works best for your project.

---

## :material-console: **Method 1: Composer (Recommended)**

The simplest way to install phpSPA is using Composer:

```bash
composer require dconco/phpspa
```

### :white_check_mark: **Verify Installation**

```php
<?php
require 'vendor/autoload.php';

use phpSPA\App;
use phpSPA\Component;

echo "phpSPA installed successfully!";
```

!!! success "Advantages of Composer"
    - Automatic dependency management
    - Easy updates with `composer update`
    - PSR-4 autoloading included
    - Works with existing Composer projects

---

## :material-download: **Method 2: Manual Installation**

If you prefer not to use Composer:

### Download the Repository

```bash
git clone https://github.com/dconco/phpspa.git
cd phpspa
```

### Include Required Files

```php
<?php
// Include the core phpSPA files
require_once 'phpspa/app/client/App.php';
require_once 'phpspa/app/client/Component.php';
require_once 'phpspa/app/client/Component/Component.php';

use phpSPA\App;
use phpSPA\Component;
```

!!! warning "Manual Installation Considerations"
    You'll need to manually manage updates and ensure all dependencies are properly included.

---

## :material-server: **Method 3: Framework Integration**

phpSPA can be integrated into existing PHP frameworks:

=== ":material-laravel: Laravel"

    ```bash
    composer require dconco/phpspa
    ```

    Add to your `config/app.php`:

    ```php
    'providers' => [
        // ... other providers
        phpSPA\Providers\LaravelServiceProvider::class,
    ],
    ```

=== ":material-symfony: Symfony"

    ```bash
    composer require dconco/phpspa
    ```

    Register the bundle in `config/bundles.php`:

    ```php
    return [
        // ... other bundles
        phpSPA\SymfonyBundle\phpSPABundle::class => ['all' => true],
    ];
    ```

=== ":material-code-tags: CodeIgniter"

    ```bash
    composer require dconco/phpspa
    ```

    Load phpSPA in your controller:

    ```php
    use phpSPA\App;
    use phpSPA\Component;
    
    class HomeController extends BaseController {
        public function index() {
            $app = new App($this->layout());
            // ... configure components
            return $app->run();
        }
    }
    ```

---

## :material-web: **Client-Side JavaScript**

phpSPA requires a small JavaScript library for reactive behavior:

### :material-link: **CDN (Recommended)**

Add to your layout template:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
```

### :material-download: **Local Installation**

Download and host locally:

```bash
# Download the latest release
curl -L https://github.com/dconco/phpspa-js/releases/latest/download/phpspa.min.js -o public/js/phpspa.min.js
```

```html
<script src="/js/phpspa.min.js"></script>
```

### :material-package-variant: **NPM**

For projects using npm:

```bash
npm install phpspa-js
```

```html
<script src="node_modules/phpspa-js/dist/phpspa.min.js"></script>
```

---

## :gear: **System Requirements**

### :material-language-php: **PHP Requirements**

| Component       | Minimum           | Recommended        |
| --------------- | ----------------- | ------------------ |
| **PHP Version** | 8.2               | 8.3+               |
| **Memory**      | 32MB              | 128MB+             |
| **Extensions**  | `json`, `session` | `zlib`, `mbstring` |

### :material-web: **Web Server**

phpSPA works with any web server that supports PHP:

=== ":material-apache: Apache"

    Ensure `mod_rewrite` is enabled for clean URLs:

    ```apache title=".htaccess"
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
    ```

=== ":material-server: Nginx"

    Configure URL rewriting:

    ```nginx title="nginx.conf"
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    ```

=== ":material-console: Built-in Server"

    For development:

    ```bash
    php -S localhost:8000
    ```

---

## :white_check_mark: **Post-Installation Verification**

Create a simple test file to verify everything is working:

```php title="test.php"
<?php
require 'vendor/autoload.php'; // or your manual includes

use phpSPA\App;
use phpSPA\Component;

function testLayout() {
    return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><title>phpSPA Test</title></head>
        <body>
            <div id="app"></div>
            <script src="https://cdn.jsdelivr.net/npm/phpspa-js@latest"></script>
        </body>
        </html>
    HTML;
}

function testComponent() {
    return '<h1>✅ phpSPA is working!</h1>';
}

$app = new App(testLayout);
$component = new Component(testComponent);
$component->route('/');

$app->attach($component)->run();
```

Run the test:

```bash
php -S localhost:8000 test.php
```

Visit [http://localhost:8000](http://localhost:8000) — you should see "✅ phpSPA is working!"

---

## :material-update: **Keeping Up to Date**

### :material-console: **Composer Updates**

```bash
# Update to latest version
composer update dconco/phpspa

# Update to specific version
composer require dconco/phpspa:^1.1.5
```

### :material-bell: **Update Notifications**

Watch the GitHub repository for new releases:

- [:fontawesome-brands-github: **Watch Releases**](https://github.com/dconco/phpspa/releases)
- [:material-rss: **Release Feed**](https://github.com/dconco/phpspa/releases.atom)

---

## :material-wrench: **Development Setup**

For contributing to phpSPA or running from source:

```bash
# Clone the repository
git clone https://github.com/dconco/phpspa.git
cd phpspa

# Install development dependencies
composer install --dev

# Run tests
./vendor/bin/phpunit

# Start development server
php -S localhost:8000 -t template/
```

---

## :warning: **Troubleshooting**

### Common Installation Issues

!!! bug "Composer not found"
    Install Composer from [getcomposer.org](https://getcomposer.org/)

!!! bug "PHP version too old"
    phpSPA requires PHP 8.2+. Update PHP or use an older phpSPA version.

!!! bug "Class not found errors"
    Ensure `vendor/autoload.php` is included, or check manual includes.

!!! bug "JavaScript errors"
    Verify the `phpspa-js` script is loaded and accessible.

### :sos: **Getting Help**

- [:fontawesome-brands-github: **GitHub Issues**](https://github.com/dconco/phpspa/issues)
- [:fontawesome-brands-discord: **Discord Community**](https://discord.gg/FeVQs73C)
- [:material-email: **Email Support**](mailto:concodave@gmail.com)

---

!!! success "Ready to Build!"
    phpSPA is now installed and ready to use. Next, learn about [creating your first component](first-app.md) or jump to the [core concepts](concepts/components.md).
