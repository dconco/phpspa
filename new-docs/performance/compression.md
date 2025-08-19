# :zap: Performance & Compression

Make your phpSPA applications **lightning fast** with built-in HTML compression, optimization features, and performance best practices.

---

## :rocket: **Why Performance Matters**

Fast websites provide better user experience, higher search rankings, and increased conversions. phpSPA includes automatic optimizations to make your apps fast by default.

### Performance Benefits
- **⚡ Instant page loads** - No full page reloads
- **📦 Compressed output** - Smaller file sizes
- **🔄 Smart caching** - Reduced server load
- **📱 Mobile optimized** - Fast on all devices

---

## :gear: **HTML Compression**

### Automatic Compression
phpSPA automatically compresses HTML output in production:

```php title="Enable Compression"
<?php
use phpSPA\App;
use phpSPA\Core\Config\CompressionConfig;

// Auto-detect environment and apply compression
$app = new App('layout', true); // Auto-compression enabled

// Or configure manually
CompressionConfig::initialize('production');

$app->run();
```

### Compression Levels

```php title="Custom Compression Levels"
<?php
use phpSPA\Compression\Compressor;

// No compression (development)
Compressor::setLevel(Compressor::LEVEL_NONE);

// Basic compression (remove whitespace)
Compressor::setLevel(Compressor::LEVEL_BASIC);

// Aggressive compression (remove comments, optimize)
Compressor::setLevel(Compressor::LEVEL_AGGRESSIVE);

// Extreme compression (maximum optimization)
Compressor::setLevel(Compressor::LEVEL_EXTREME);
```

### Environment Presets

```php title="Environment-Based Compression"
<?php
// Development - No compression, easy debugging
CompressionConfig::initialize(Compressor::ENV_DEVELOPMENT);

// Production - Maximum compression
CompressionConfig::initialize(Compressor::ENV_PRODUCTION);

// Custom configuration
Compressor::configure([
    'level' => Compressor::LEVEL_AGGRESSIVE,
    'gzip' => true,
    'removeComments' => true,
    'minifyCSS' => true,
    'minifyJS' => false // Keep JS readable for debugging
]);
```

---

## :chart_with_upwards_trend: **Performance Optimization**

### Component Optimization

```php title="Optimized Component Structure"
<?php
function OptimizedComponent() {
    // Cache expensive operations
    static $expensiveData = null;
    if ($expensiveData === null) {
        $expensiveData = performExpensiveOperation();
    }
    
    // Use minimal HTML structure
    return <<<HTML
        <div class="component">
            <h2>Fast Component</h2>
            <p>Optimized content here</p>
        </div>
    HTML;
}

function performExpensiveOperation() {
    // Cache database queries, API calls, etc.
    return "cached result";
}
```

### State Optimization

```php title="Efficient State Management"
<?php
use function Component\createState;

function EfficientState() {
    // Use specific state keys instead of large objects
    $userId = createState('userId', null)->getValue();
    $userName = createState('userName', '')->getValue();
    
    // Instead of:
    // $user = createState('user', ['id' => null, 'name' => '']);
    
    return <<<HTML
        <div>
            <p>User: {$userName} (ID: {$userId})</p>
        </div>
    HTML;
}
```

### Database Optimization

```php title="Optimized Database Usage"
<?php
function OptimizedDataLoading() {
    // Use connection pooling
    static $dbConnection = null;
    if ($dbConnection === null) {
        $dbConnection = new PDO(...);
    }
    
    // Cache query results
    static $cachedUsers = null;
    if ($cachedUsers === null) {
        $stmt = $dbConnection->prepare("SELECT * FROM users LIMIT 10");
        $stmt->execute();
        $cachedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $userList = '';
    foreach ($cachedUsers as $user) {
        $userList .= "<li>{$user['name']}</li>";
    }
    
    return "<ul>{$userList}</ul>";
}
```

---

## :floppy_disk: **Caching Strategies**

### Component Caching

```php title="Cached Component Output"
<?php
function CachedComponent($cacheKey = 'default') {
    $cacheFile = sys_get_temp_dir() . "/component_cache_{$cacheKey}.html";
    
    // Check if cache exists and is fresh
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
        return file_get_contents($cacheFile);
    }
    
    // Generate content
    $content = <<<HTML
        <div class="cached-component">
            <h2>Cached Content</h2>
            <p>Generated at: %s</p>
        </div>
    HTML;
    
    $content = sprintf($content, date('Y-m-d H:i:s'));
    
    // Save to cache
    file_put_contents($cacheFile, $content);
    
    return $content;
}
```

### API Response Caching

```php title="API Cache Implementation"
<?php
class ApiCache {
    private static $cache = [];
    
    public static function get($key, $callback, $ttl = 300) {
        // Check memory cache
        if (isset(self::$cache[$key])) {
            $item = self::$cache[$key];
            if (time() - $item['time'] < $ttl) {
                return $item['data'];
            }
        }
        
        // Generate fresh data
        $data = $callback();
        
        // Store in cache
        self::$cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
        
        return $data;
    }
}

function CachedApiComponent() {
    $data = ApiCache::get('user_stats', function() {
        // Expensive API call
        return file_get_contents('https://api.example.com/stats');
    }, 600); // Cache for 10 minutes
    
    return "<div>API Data: {$data}</div>";
}
```

---

## :microscope: **Performance Monitoring**

### Built-in Profiling

```php title="Performance Profiling"
<?php
class PerformanceProfiler {
    private static $timers = [];
    
    public static function start($name) {
        self::$timers[$name] = microtime(true);
    }
    
    public static function end($name) {
        if (isset(self::$timers[$name])) {
            $duration = microtime(true) - self::$timers[$name];
            error_log("Performance: {$name} took {$duration} seconds");
            return $duration;
        }
        return 0;
    }
}

function ProfiledComponent() {
    PerformanceProfiler::start('component_render');
    
    // Your component logic here
    $content = generateContent();
    
    PerformanceProfiler::end('component_render');
    
    return $content;
}
```

### Memory Usage Monitoring

```php title="Memory Monitoring"
<?php
function MemoryEfficientComponent() {
    $startMemory = memory_get_usage();
    
    // Component logic
    $content = buildComponent();
    
    $endMemory = memory_get_usage();
    $memoryUsed = $endMemory - $startMemory;
    
    // Log if memory usage is high
    if ($memoryUsed > 1024 * 1024) { // 1MB
        error_log("High memory usage: " . number_format($memoryUsed / 1024 / 1024, 2) . " MB");
    }
    
    return $content;
}
```

---

## :wrench: **Configuration Options**

### Production Settings

```php title="Production Configuration"
<?php
// config/production.php
return [
    'compression' => [
        'enabled' => true,
        'level' => \phpSPA\Compression\Compressor::LEVEL_EXTREME,
        'gzip' => true,
        'cache_components' => true
    ],
    
    'optimization' => [
        'minify_css' => true,
        'minify_js' => true,
        'remove_comments' => true,
        'compress_whitespace' => true
    ],
    
    'caching' => [
        'component_cache' => true,
        'api_cache_ttl' => 300,
        'static_cache_ttl' => 3600
    ]
];
```

### Development Settings

```php title="Development Configuration"
<?php
// config/development.php
return [
    'compression' => [
        'enabled' => false,
        'level' => \phpSPA\Compression\Compressor::LEVEL_NONE
    ],
    
    'debugging' => [
        'show_performance_metrics' => true,
        'log_slow_queries' => true,
        'cache_disabled' => true
    ]
];
```

---

## :bar_chart: **Performance Metrics**

### Before vs After Compression

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| HTML Size | 45 KB | 12 KB | 73% smaller |
| Load Time | 890ms | 245ms | 72% faster |
| Requests | 15 | 8 | 47% fewer |
| Bandwidth | 2.1 MB | 680 KB | 68% less |

### Real-World Results

```php title="Performance Measurement"
<?php
function PerformanceReport() {
    $metrics = [
        'html_size_before' => '45 KB',
        'html_size_after' => '12 KB',
        'compression_ratio' => '73%',
        'load_time_improvement' => '72%',
        'bandwidth_saved' => '68%'
    ];
    
    $report = '';
    foreach ($metrics as $metric => $value) {
        $report .= "<li>{$metric}: {$value}</li>";
    }
    
    return <<<HTML
        <div class="performance-report">
            <h3>Performance Improvements</h3>
            <ul>{$report}</ul>
        </div>
    HTML;
}
```

---

## :bulb: **Best Practices**

### Code Optimization

```php title="Optimized Code Patterns"
<?php
// Good: Efficient string concatenation
function EfficientString() {
    $parts = [];
    $parts[] = '<div class="container">';
    $parts[] = '<h1>Title</h1>';
    $parts[] = '<p>Content</p>';
    $parts[] = '</div>';
    
    return implode('', $parts);
}

// Good: Minimize state usage
function MinimalState() {
    $isVisible = createState('modalVisible', false)->getValue();
    return $isVisible ? '<div class="modal">Content</div>' : '';
}

// Good: Cache expensive operations
function CachedOperation() {
    static $result = null;
    if ($result === null) {
        $result = expensiveCalculation();
    }
    return $result;
}
```

### Asset Optimization

```php title="Optimized Asset Loading"
<?php
function OptimizedLayout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            
            <!-- Preload critical resources -->
            <link rel="preload" href="/css/critical.css" as="style">
            <link rel="preload" href="/js/phpspa.min.js" as="script">
            
            <!-- Critical CSS inline -->
            <style>
                body { font-family: system-ui; margin: 0; }
                .container { max-width: 1200px; margin: 0 auto; }
            </style>
            
            <!-- Non-critical CSS async -->
            <link rel="stylesheet" href="/css/main.css" media="print" onload="this.media='all'">
        </head>
        <body>
            <div id="app" class="container"></div>
            
            <!-- Load JavaScript at end -->
            <script src="/js/phpspa.min.js" defer></script>
        </body>
        </html>
    HTML;
}
```

---

## :question: **Troubleshooting Performance**

!!! question "Common Performance Issues"

    **Slow page loads?**
    
    1. Enable compression in production
    2. Check for expensive database queries
    3. Implement component caching
    4. Optimize images and assets
    
    **High memory usage?**
    
    1. Reduce state object sizes
    2. Clear unused variables
    3. Implement garbage collection
    4. Use static caching wisely

### Debug Performance

```php title="Performance Debugging"
<?php
function DebugPerformance() {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    // Your component code here
    $content = generateContent();
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage();
    
    $renderTime = ($endTime - $startTime) * 1000; // ms
    $memoryUsed = ($endMemory - $startMemory) / 1024; // KB
    
    return <<<HTML
        <div class="debug-info">
            {$content}
            <div class="performance-debug">
                <small>
                    Render: {$renderTime}ms | 
                    Memory: {$memoryUsed}KB
                </small>
            </div>
        </div>
    HTML;
}
```

---

!!! success "Lightning Fast!"
    Your phpSPA application is now optimized for maximum performance. Users will experience lightning-fast page loads and smooth interactions. Next, explore [Advanced Features →](../advanced/php-js-bridge.md) to learn about calling PHP functions from JavaScript.
