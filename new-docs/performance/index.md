# ⚡ Performance

phpSPA is designed for optimal performance with built-in compression, efficient component rendering, and smart optimization features. This guide covers how to maximize your application's performance.

!!! success "Performance First"
    phpSPA includes automatic HTML/CSS/JS compression, smart component caching, and environment-based optimization out of the box.

---

## 🗜️ HTML Compression & Minification

phpSPA includes a comprehensive compression system that can reduce payload sizes by 15-84%, improving load times and reducing bandwidth usage.

### Quick Start with Auto-Compression

```php
use phpSPA\App;
use phpSPA\Compression\Compressor;

// Automatic compression based on environment
$app = new App(fn() => require 'Layout.php');

// Auto-compression based on content size
$app = new App(fn() => require 'Layout.php')
    ->compression(Compressor::LEVEL_AUTO, true);
```

### Manual Compression Configuration

```php
use phpSPA\App;
use phpSPA\Compression\Compressor;

// Custom compression settings
$app = new App(fn() => require 'Layout.php')
    ->compression(Compressor::LEVEL_AGGRESSIVE, true);

// Environment-based compression
$app = new App(fn() => require 'Layout.php')
    ->compressionEnvironment(Compressor::ENV_PRODUCTION);

// Disable compression for development
$app = new App(fn() => require 'Layout.php', false)
    ->compression(Compressor::LEVEL_NONE, false);
```

### Compression Levels

| Level | Constant           | Description                             | Use Case                  |
| ----- | ------------------ | --------------------------------------- | ------------------------- |
| **0** | `LEVEL_NONE`       | No compression                          | Development, debugging    |
| **1** | `LEVEL_AUTO`       | Smart compression based on content size | General purpose           |
| **2** | `LEVEL_BASIC`      | Remove comments, extra whitespace       | Light optimization        |
| **3** | `LEVEL_AGGRESSIVE` | Basic + JS/CSS minification             | Production sites          |
| **4** | `LEVEL_EXTREME`    | Maximum compression + optimization      | High-traffic applications |

### Environment Auto-Detection

phpSPA automatically detects your environment and applies appropriate compression:

```php
// Development: No compression (faster builds)
// - localhost, 127.0.0.1, *.local, *.dev domains
// - CLI server mode
// - APP_ENV=development

// Staging: Basic compression (balanced)
// - staging subdomains  
// - APP_ENV=staging

// Production: Aggressive compression (optimized)
// - All other environments
// - APP_ENV=production
```

### Check Gzip Support

```php
use phpSPA\Compression\Compressor;

// Verify gzip compression is available
if (Compressor::supportsGzip()) {
    echo "✅ Gzip compression is supported";
} else {
    echo "❌ Gzip not available - enable zlib extension";
}
```

---

## 🚀 Component Performance

### Efficient Component Design

```php
// ✅ Good: Simple, focused components
function UserCard($user) {
    return <<<HTML
        <div class="user-card">
            <img src="{$user['avatar']}" alt="{htmlspecialchars($user['name'])}">
            <h3>{htmlspecialchars($user['name'])}</h3>
            <p>{htmlspecialchars($user['role'])}</p>
        </div>
    HTML;
}

// ✅ Good: Reusable components with parameters
function ProductList($products, $showPrices = true) {
    $items = array_map(function($product) use ($showPrices) {
        $price = $showPrices ? "<span class='price'>\${$product['price']}</span>" : '';
        return <<<HTML
            <div class="product">
                <h4>{htmlspecialchars($product['name'])}</h4>
                {$price}
            </div>
        HTML;
    }, $products);
    
    return '<div class="product-list">' . implode('', $items) . '</div>';
}
```

### Avoid Performance Anti-Patterns

```php
// ❌ Bad: Heavy processing in component
function SlowComponent() {
    // Don't do expensive operations here
    $data = file_get_contents('https://api.example.com/slow-endpoint');
    $processed = processLargeDataset($data);
    
    return "<div>$processed</div>";
}

// ✅ Good: Pre-process data, pass as parameters
function FastComponent($preProcessedData) {
    return <<<HTML
        <div class="fast-component">
            {$preProcessedData}
        </div>
    HTML;
}

// ✅ Good: Use data that's already available
function OptimizedComponent() {
    $cachedData = getFromCache('user_data');
    
    return <<<HTML
        <div class="optimized">
            {$cachedData}
        </div>
    HTML;
}
```

---

## 🧠 State Management Performance

### Efficient State Updates

```php
// ✅ Good: Minimal state, targeted updates
function Counter() {
    $count = createState('count', 0);
    
    return <<<HTML
        <div class="counter">
            <span>Count: {$count}</span>
            <button onclick="increment()">+</button>
            <button onclick="decrement()">-</button>
        </div>
        
        <script data-type="phpspa/script">
            function increment() {
                phpspa.setState('count', phpspa.getState('count') + 1);
            }
            
            function decrement() {
                phpspa.setState('count', phpspa.getState('count') - 1);
            }
        </script>
    HTML;
}

// ✅ Good: Batch state updates when possible
function UserProfile() {
    return <<<HTML
        <div class="profile">
            <button onclick="updateProfile()">Update All</button>
        </div>
        
        <script data-type="phpspa/script">
            function updateProfile() {
                // Batch multiple state updates
                phpspa.setState('user_name', 'John Doe');
                phpspa.setState('user_email', 'john@example.com');
                phpspa.setState('user_role', 'admin');
            }
        </script>
    HTML;
}
```

### State Performance Tips

!!! tip "State Optimization"

    1. **Keep state minimal**: Only store what you need
    2. **Use specific keys**: Avoid large nested objects
    3. **Batch updates**: Update multiple state values together when possible
    4. **Clean up**: Remove unused state when components unmount

---

## 📦 Asset Optimization

### CSS & JavaScript Minification

phpSPA automatically minifies CSS and JavaScript when compression is enabled:

```php
function StyledComponent() {
    return <<<HTML
        <div class="styled-component">
            <h2>Optimized Component</h2>
            <p>CSS and JS will be automatically minified</p>
        </div>
        
        <style data-type="phpspa/style">
            .styled-component {
                background: linear-gradient(45deg, #667eea, #764ba2);
                padding: 20px;
                border-radius: 8px;
                color: white;
            }
            
            .styled-component h2 {
                margin: 0 0 10px 0;
                font-size: 1.5rem;
            }
        </style>
        
        <script data-type="phpspa/script">
            // This JavaScript will be minified in production
            function handleClick() {
                console.log('Component clicked');
                // Complex logic here gets compressed
                const element = document.querySelector('.styled-component');
                element.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 200);
            }
        </script>
    HTML;
}
```

### Image Optimization

```php
function OptimizedImages() {
    return <<<HTML
        <div class="image-gallery">
            <!-- Use appropriate image formats -->
            <img src="/images/hero.webp" 
                 alt="Hero image"
                 width="800" 
                 height="400"
                 loading="lazy">
            
            <!-- Responsive images -->
            <picture>
                <source media="(min-width: 800px)" srcset="/images/large.webp">
                <source media="(min-width: 400px)" srcset="/images/medium.webp">
                <img src="/images/small.webp" alt="Responsive image">
            </picture>
        </div>
    HTML;
}
```

---

## 🏗️ Production Optimization

### Environment-Specific Configuration

```php
// config/app.php
<?php

use phpSPA\App;
use phpSPA\Compression\Compressor;

// Production configuration
if ($_ENV['APP_ENV'] === 'production') {
    $app = new App(fn() => require 'Layout.php')
        ->compression(Compressor::LEVEL_EXTREME, true);
        
} elseif ($_ENV['APP_ENV'] === 'staging') {
    $app = new App(fn() => require 'Layout.php')
        ->compression(Compressor::LEVEL_AGGRESSIVE, true);
        
} else {
    // Development - no compression for faster builds
    $app = new App(fn() => require 'Layout.php', false)
        ->compression(Compressor::LEVEL_NONE, false);
}
```

### Server-Level Optimizations

```apache
# .htaccess - Apache configuration
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/webp "access plus 6 months"
</IfModule>
```

```nginx
# nginx.conf - Nginx configuration
location ~* \.(css|js)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
}

location ~* \.(webp|jpg|png|svg)$ {
    expires 6M;
    add_header Cache-Control "public, immutable";
}
```

### PHP-FPM Optimization

```ini
; php-fpm.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; php.ini optimizations
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0  ; Production only
```

---

## 📊 Performance Monitoring

### Measuring Compression Effectiveness

```php
function CompressionStats() {
    // This would be logged/monitored in production
    $originalSize = strlen($uncompressedOutput);
    $compressedSize = strlen($compressedOutput);
    $savings = round((1 - $compressedSize / $originalSize) * 100, 1);
    
    return <<<HTML
        <div class="compression-stats">
            <p>Original size: {$originalSize} bytes</p>
            <p>Compressed size: {$compressedSize} bytes</p>
            <p>Space saved: {$savings}%</p>
        </div>
    HTML;
}
```

### Performance Best Practices

!!! success "Development Best Practices"

    1. **Enable compression in production**: Use `LEVEL_AGGRESSIVE` or `LEVEL_EXTREME`
    2. **Profile your components**: Identify slow components and optimize them
    3. **Minimize state usage**: Keep state lean and focused
    4. **Optimize images**: Use WebP format and lazy loading
    5. **Leverage browser caching**: Set appropriate cache headers

!!! info "Production Checklist"

    - ✅ Compression enabled (`LEVEL_AGGRESSIVE` or higher)
    - ✅ Gzip compression active
    - ✅ Static assets cached (CSS, JS, images)
    - ✅ OPcache enabled
    - ✅ PHP-FPM properly configured
    - ✅ CDN for static assets (if applicable)

!!! warning "Avoid Common Pitfalls"

    - ❌ Don't disable compression in production
    - ❌ Don't put heavy processing in component functions
    - ❌ Don't create unnecessary state
    - ❌ Don't ignore browser caching headers
    - ❌ Don't serve unoptimized images

---

## 🚀 Next Steps

Explore related performance topics:

<div class="buttons" markdown>
[Compression Guide](compression/){ .md-button .md-button--primary }
[Component Optimization](../components/){ .md-button }
[State Management](../state/){ .md-button }
[Production Deployment](deployment/){ .md-button }
</div>

---

## 💡 Performance Philosophy

**phpSPA is built for speed.** The framework provides:

- **🗜️ Automatic Compression**: 15-84% size reduction out of the box
- **⚡ Smart Component Rendering**: Efficient component lifecycle
- **🧠 Optimized State Management**: Minimal overhead state updates
- **🏗️ Production-Ready**: Environment-aware optimizations
- **📦 Asset Minification**: Automatic CSS/JS compression

Remember: **Performance is a feature, not an afterthought!**
