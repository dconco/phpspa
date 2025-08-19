# Method Chaining

## Overview

phpSPA v1.1.5 introduces method chaining support for the `App` class, enabling fluent API calls for cleaner and more expressive application configuration. This allows you to chain multiple method calls together for better code readability and organization.

## Key Features

- **Fluent API**: Chain multiple configuration methods
- **Improved Readability**: More expressive application setup
- **Backward Compatibility**: Existing code continues to work
- **Complete Coverage**: All major App methods support chaining
- **Runtime Configuration**: Chain configuration with execution

## Basic Method Chaining

### Simple Chaining

```php
<?php
use phpSPA\App;

// Traditional approach
$app = new App(require 'Layout.php');
$app->attach(require 'components/Login.php');
$app->defaultTargetID('app');
$app->run();

// Method chaining approach
$app = (new App(require 'Layout.php'))
    ->attach(require 'components/Login.php')
    ->defaultTargetID('app')
    ->run();
```

### Extended Chaining

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = (new App(require 'Layout.php'))
    ->attach(require 'components/Dashboard.php')
    ->attach(require 'components/Login.php')
    ->attach(require 'components/Profile.php')
    ->defaultTargetID('app')
    ->defaultToCaseSensitive()
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->run();
```

## Available Chainable Methods

### Core Configuration

#### attach()
Attach components to the application:

```php
<?php
$app = (new App('layout'))
    ->attach(require 'components/Home.php')
    ->attach(require 'components/About.php')
    ->attach(require 'components/Contact.php')
    ->run();
```

#### defaultTargetID()
Set the default target ID for component rendering:

```php
<?php
$app = (new App('layout'))
    ->defaultTargetID('main-content')
    ->attach(require 'components/App.php')
    ->run();
```

#### defaultToCaseSensitive()
Enable case-sensitive routing:

```php
<?php
$app = (new App('layout'))
    ->defaultToCaseSensitive()
    ->attach(require 'components/Routes.php')
    ->run();
```

### Performance Methods

#### compression()
Configure HTML compression:

```php
<?php
use phpSPA\Compression\Compressor;

$app = (new App('layout'))
    ->compression(Compressor::LEVEL_EXTREME, true)
    ->attach(require 'components/App.php')
    ->run();
```

#### compressionEnvironment()
Set compression based on environment:

```php
<?php
use phpSPA\Compression\Compressor;

$app = (new App('layout'))
    ->compressionEnvironment(Compressor::ENV_PRODUCTION)
    ->attach(require 'components/App.php')
    ->run();
```

### Security Methods

#### cors()
Enable CORS configuration:

```php
<?php
$app = (new App('layout'))
    ->cors()
    ->attach(require 'components/Api.php')
    ->run();
```

#### corsConfig()
Custom CORS configuration:

```php
<?php
$corsConfig = [
    'origin' => ['https://example.com'],
    'methods' => ['GET', 'POST'],
    'headers' => ['Content-Type', 'Authorization']
];

$app = (new App('layout'))
    ->corsConfig($corsConfig)
    ->attach(require 'components/App.php')
    ->run();
```

## Advanced Chaining Patterns

### Environment-Based Configuration

```php
<?php
use phpSPA\App;
use phpSPA\Compression\Compressor;

$app = new App(require 'Layout.php');

// Configure based on environment
if ($_ENV['APP_ENV'] === 'production') {
    $app = $app
        ->compression(Compressor::LEVEL_EXTREME, true)
        ->cors();
} else {
    $app = $app
        ->compression(Compressor::LEVEL_NONE);
}

$app = $app
    ->attach(require 'components/App.php')
    ->defaultTargetID('app')
    ->run();
```

### Conditional Chaining

```php
<?php
function configureApp($app, $config) {
    $app = $app->defaultTargetID($config['target_id']);
    
    if ($config['compression']) {
        $app = $app->compression(
            $config['compression_level'], 
            $config['gzip']
        );
    }
    
    if ($config['cors']) {
        $app = $app->cors();
    }
    
    if ($config['case_sensitive']) {
        $app = $app->defaultToCaseSensitive();
    }
    
    return $app;
}

$config = [
    'target_id' => 'app',
    'compression' => true,
    'compression_level' => Compressor::LEVEL_AUTO,
    'gzip' => true,
    'cors' => false,
    'case_sensitive' => true
];

$app = configureApp(new App('layout'), $config)
    ->attach(require 'components/App.php')
    ->run();
```

### Component Groups

```php
<?php
function attachAuthComponents($app) {
    return $app
        ->attach(require 'components/auth/Login.php')
        ->attach(require 'components/auth/Register.php')
        ->attach(require 'components/auth/ForgotPassword.php');
}

function attachDashboardComponents($app) {
    return $app
        ->attach(require 'components/dashboard/Overview.php')
        ->attach(require 'components/dashboard/Analytics.php')
        ->attach(require 'components/dashboard/Settings.php');
}

$app = (new App(require 'Layout.php'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors();

$app = attachAuthComponents($app);
$app = attachDashboardComponents($app);

$app->defaultTargetID('app')->run();
```

## Configuration Factory Pattern

### App Configuration Factory

```php
<?php
class AppFactory {
    private $config;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'layout' => 'Layout.php',
            'target_id' => 'app',
            'compression' => true,
            'compression_level' => Compressor::LEVEL_AUTO,
            'gzip' => true,
            'cors' => false,
            'case_sensitive' => false,
            'components' => []
        ], $config);
    }
    
    public function create() {
        $app = new App(require $this->config['layout']);
        
        // Apply compression
        if ($this->config['compression']) {
            $app = $app->compression(
                $this->config['compression_level'], 
                $this->config['gzip']
            );
        }
        
        // Apply CORS
        if ($this->config['cors']) {
            $app = $app->cors();
        }
        
        // Apply case sensitivity
        if ($this->config['case_sensitive']) {
            $app = $app->defaultToCaseSensitive();
        }
        
        // Attach components
        foreach ($this->config['components'] as $component) {
            $app = $app->attach(require $component);
        }
        
        return $app->defaultTargetID($this->config['target_id']);
    }
}

// Usage
$factory = new AppFactory([
    'compression_level' => Compressor::LEVEL_EXTREME,
    'cors' => true,
    'case_sensitive' => true,
    'components' => [
        'components/Home.php',
        'components/About.php',
        'components/Contact.php'
    ]
]);

$app = $factory->create()->run();
```

### Environment-Specific Factory

```php
<?php
class EnvironmentAppFactory {
    public static function production($components = []) {
        return (new App(require 'Layout.php'))
            ->compression(Compressor::LEVEL_EXTREME, true)
            ->cors()
            ->defaultToCaseSensitive()
            ->attachMany($components)
            ->defaultTargetID('app');
    }
    
    public static function development($components = []) {
        return (new App(require 'Layout.php'))
            ->compression(Compressor::LEVEL_NONE)
            ->attachMany($components)
            ->defaultTargetID('app');
    }
    
    public static function staging($components = []) {
        return (new App(require 'Layout.php'))
            ->compression(Compressor::LEVEL_BASIC)
            ->cors()
            ->attachMany($components)
            ->defaultTargetID('app');
    }
}

// Usage
$components = [
    'components/App.php',
    'components/Dashboard.php'
];

$app = match($_ENV['APP_ENV']) {
    'production' => EnvironmentAppFactory::production($components),
    'staging' => EnvironmentAppFactory::staging($components),
    default => EnvironmentAppFactory::development($components)
};

$app->run();
```

## Helper Methods for Chaining

### attachMany()
Attach multiple components at once:

```php
<?php
// Implementation example (if not built-in)
class App {
    public function attachMany($components) {
        foreach ($components as $component) {
            $this->attach(require $component);
        }
        return $this;
    }
}

// Usage
$components = [
    'components/Home.php',
    'components/About.php',
    'components/Contact.php'
];

$app = (new App('layout'))
    ->attachMany($components)
    ->defaultTargetID('app')
    ->run();
```

### configureFromArray()
Configure app from array:

```php
<?php
class App {
    public function configureFromArray($config) {
        if (isset($config['target_id'])) {
            $this->defaultTargetID($config['target_id']);
        }
        
        if (isset($config['compression'])) {
            $this->compression($config['compression'], $config['gzip'] ?? false);
        }
        
        if (isset($config['cors']) && $config['cors']) {
            $this->cors();
        }
        
        if (isset($config['case_sensitive']) && $config['case_sensitive']) {
            $this->defaultToCaseSensitive();
        }
        
        return $this;
    }
}

// Usage
$config = [
    'target_id' => 'app',
    'compression' => Compressor::LEVEL_AUTO,
    'gzip' => true,
    'cors' => true,
    'case_sensitive' => false
];

$app = (new App('layout'))
    ->configureFromArray($config)
    ->attach(require 'components/App.php')
    ->run();
```

## Best Practices

### 1. Logical Grouping

```php
<?php
// Group related configurations
$app = (new App('layout'))
    // Performance configurations
    ->compression(Compressor::LEVEL_AUTO, true)
    
    // Security configurations
    ->cors()
    ->defaultToCaseSensitive()
    
    // Component configurations
    ->attach(require 'components/App.php')
    ->defaultTargetID('app')
    
    // Execute
    ->run();
```

### 2. Break Long Chains

```php
<?php
// Instead of one very long chain
$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->defaultToCaseSensitive()
    ->attach(require 'components/Home.php')
    ->attach(require 'components/About.php')
    ->attach(require 'components/Contact.php')
    ->attach(require 'components/Dashboard.php')
    ->attach(require 'components/Profile.php')
    ->defaultTargetID('app')
    ->run();

// Break into logical sections
$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->defaultToCaseSensitive();

// Attach components
$components = [
    'components/Home.php',
    'components/About.php',
    'components/Contact.php',
    'components/Dashboard.php',
    'components/Profile.php'
];

foreach ($components as $component) {
    $app = $app->attach(require $component);
}

$app->defaultTargetID('app')->run();
```

### 3. Return App Instance

Always ensure methods return the App instance:

```php
<?php
class App {
    public function customMethod($param) {
        // Do something with $param
        $this->someProperty = $param;
        
        // Always return $this for chaining
        return $this;
    }
}
```

### 4. Validate Chaining Order

Some methods may depend on others being called first:

```php
<?php
// Ensure proper order
$app = (new App('layout'))
    ->compression(Compressor::LEVEL_AUTO, true)  // Set compression first
    ->attach(require 'components/App.php')       // Then attach components
    ->defaultTargetID('app')                     // Set target
    ->run();                                     // Execute last
```

## Migration Guide

### From Traditional to Chaining

**Before:**
```php
<?php
$app = new App(require 'Layout.php');
$app->attach(require 'components/App.php');
$app->defaultTargetID('app');
$app->compression(Compressor::LEVEL_AUTO, true);
$app->cors();
$app->run();
```

**After:**
```php
<?php
$app = (new App(require 'Layout.php'))
    ->attach(require 'components/App.php')
    ->defaultTargetID('app')
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->run();
```

### Gradual Migration

You can mix traditional and chaining approaches:

```php
<?php
$app = new App(require 'Layout.php');

// Traditional method calls
$app->attach(require 'components/Legacy.php');

// Switch to chaining
$app = $app
    ->compression(Compressor::LEVEL_AUTO, true)
    ->cors()
    ->defaultTargetID('app')
    ->run();
```

Method chaining provides a more elegant and readable way to configure your phpSPA applications while maintaining full backward compatibility with existing code.
