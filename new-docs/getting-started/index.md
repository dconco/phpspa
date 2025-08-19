# 🎯 Getting Started with phpSPA

Welcome to **phpSPA** — the component-based PHP library that brings React-like development experience to server-side applications. This guide will get you building modern, interactive web applications in minutes.

!!! tip "What You'll Learn"
    
    - **Core Concepts**: Understanding phpSPA's component architecture
    - **Quick Setup**: Get a working application in under 5 minutes
    - **Essential Patterns**: Building blocks for scalable applications
    - **Best Practices**: Production-ready development workflows

---

## 🧠 Core Philosophy

phpSPA is built on three fundamental principles:

<div class="grid cards" markdown>

-   **🧩 Component-Driven**
    
    ---
    
    Everything is a component. Build your UI as reusable, composable functions that return HTML.

-   **🧠 State-Reactive**
    
    ---
    
    State changes automatically trigger UI updates. No manual DOM manipulation needed.

-   **⚡ Performance-First**
    
    ---
    
    Zero full-page reloads with intelligent caching, compression, and optimization built-in.

</div>

---

## 🔍 Understanding Components

In phpSPA, components are **PHP functions** that return HTML. Think of them like React components, but written in pure PHP:

```php
function WelcomeMessage() {
    $user = "Developer";
    
    return <<<HTML
        <div class="welcome">
            <h1>Hello, {$user}!</h1>
            <p>Welcome to phpSPA</p>
        </div>
    HTML;
}
```

### Component Features

| Feature | Description | Example |
|---------|-------------|---------|
| **Pure Functions** | Components are just PHP functions | `function MyComponent() { ... }` |
| **HTML Templates** | Return HTML using heredoc syntax | `return <<<HTML ... HTML;` |
| **State Integration** | Use `createState()` for reactive data | `$count = createState('count', 0);` |
| **Props Support** | Accept parameters like any function | `function User($name) { ... }` |

---

## 🌊 State Management Flow

phpSPA's state system creates a reactive connection between your PHP backend and the frontend:

```mermaid
graph LR
    A[User Interaction] --> B[phpspa.setState()]
    B --> C[AJAX Request]
    C --> D[PHP Component Re-render]
    D --> E[DOM Update]
    E --> F[UI Reflects New State]
    
    style B fill:#667eea
    style D fill:#764ba2
    style F fill:#4caf50
```

### State in Action

```php
function Counter() {
    // Create reactive state variable
    $count = createState('counter', 0);
    
    return <<<HTML
        <div class="counter">
            <h2>Count: {$count}</h2>
            <button onclick="phpspa.setState('counter', {$count} + 1)">
                Increment
            </button>
            <button onclick="phpspa.setState('counter', 0)">
                Reset
            </button>
        </div>
    HTML;
}
```

!!! info "Automatic Re-rendering"
    
    When `phpspa.setState()` is called, phpSPA automatically re-renders the component with the new state and updates only the changed parts of the DOM.

---

## 🧭 Routing Architecture

phpSPA handles routing both on the server and client side, providing SPA-like navigation without full page reloads:

### Basic Routing Pattern

```php
use phpSPA\App;
use phpSPA\Component;

$app = new App($layout);

// Define routes using method chaining
$app->attach(
    (new Component('HomePage'))
        ->route('/')
        ->title('Home')
        ->method('GET')
);

$app->attach(
    (new Component('UserProfile'))
        ->route('/user/{id}')
        ->title('User Profile')
        ->method('GET')
);

$app->run();
```

### Route Parameters

phpSPA automatically extracts route parameters and passes them to your components:

```php
function UserProfile($id) {
    // $id contains the value from the URL: /user/123
    $user = getUserById($id);
    
    return <<<HTML
        <div class="profile">
            <h1>{$user['name']}</h1>
            <p>User ID: {$id}</p>
        </div>
    HTML;
}
```

---

## 🏗️ Application Structure

A typical phpSPA application follows this structure:

```
my-phpspa-app/
├── index.php          # Application entry point
├── Layout.php         # Base HTML template
├── components/        # Component functions
│   ├── HomePage.php
│   ├── UserList.php
│   └── ContactForm.php
├── assets/           # Static files
│   ├── css/
│   └── js/
└── vendor/           # Composer dependencies
```

### The Layout Component

Your layout defines the base HTML structure:

```php
function Layout() {
    return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>My phpSPA App</title>
            <link rel="stylesheet" href="/assets/css/app.css">
        </head>
        <body>
            <nav>
                <Component.Link to="/" label="Home" />
                <Component.Link to="/users" label="Users" />
                <Component.Link to="/contact" label="Contact" />
            </nav>
            
            <main id="app">
                <!-- Components render here -->
            </main>
            
            <script src="https://unpkg.com/phpspa-js"></script>
        </body>
        </html>
    HTML;
}
```

---

## 🎯 Development Workflow

### 1. **Create Component**
Write a PHP function that returns HTML

### 2. **Add State (Optional)**
Use `createState()` for reactive data

### 3. **Define Route**
Attach component to URL pattern

### 4. **Test & Iterate**
Auto-reload keeps development fast

### 5. **Deploy**
Built-in optimization for production

---

## ⚡ Performance Features

phpSPA includes powerful performance optimizations out of the box:

<div class="feature-grid" markdown>

### 🗜️ HTML Compression

- **Multi-level compression**: Auto, Basic, Aggressive, Extreme
- **JS/CSS minification**: Automatic with ASI support  
- **Gzip encoding**: When supported by server
- **Environment detection**: Auto-optimizes based on dev/prod

### 🧠 Smart Caching

- **Component-level caching**: Cache expensive operations
- **State persistence**: Maintain state across requests
- **Browser caching**: Intelligent cache headers
- **CDN ready**: Optimized for content delivery

### 🔄 SPA Navigation

- **Zero full reloads**: Update only changed content
- **History API**: Full browser history support
- **Prefetching**: Load components before needed
- **Lazy loading**: Components load on demand

</div>

---

## 🛡️ Built-in Security

Security is a top priority in phpSPA:

| Security Feature | Implementation | Benefit |
|------------------|----------------|---------|
| **CSRF Protection** | `<Component.Csrf />` | Prevents cross-site attacks |
| **Input Validation** | Built-in sanitization | Safe user input handling |
| **Type Safety** | Parameter validation | Prevents type confusion |
| **Secure Headers** | Automatic headers | XSS and injection protection |

---

## 🎨 Styling & Assets

### Component-Scoped Styles

```php
function StyledComponent() {
    return <<<HTML
        <div class="styled-component">
            <h2>Styled Component</h2>
            <p>This component has its own styles.</p>
        </div>
        
        <style data-type="phpspa/css">
            .styled-component {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2rem;
                border-radius: 1rem;
            }
        </style>
    HTML;
}
```

### Component Scripts

```php
function InteractiveComponent() {
    return <<<HTML
        <div class="interactive">
            <button onclick="handleClick()">Click Me</button>
        </div>
        
        <script data-type="phpspa/script">
            function handleClick() {
                alert('Component script executed!');
            }
        </script>
    HTML;
}
```

---

## 📋 Requirements

<div class="grid cards" markdown>

-   **🐘 PHP 8.2+**
    
    ---
    
    Modern PHP features for clean, type-safe code

-   **🌐 Web Server**
    
    ---
    
    Apache, Nginx, or PHP built-in server

-   **📦 Composer (Optional)**
    
    ---
    
    For dependency management and autoloading

-   **🌍 Modern Browser**
    
    ---
    
    JavaScript ES6+ support for client features

</div>

---

## 🚀 Next Steps

Ready to build your first phpSPA application? Choose your path:

<div class="buttons" markdown>
[Quick Start Tutorial](quick-start.md){ .md-button .md-button--primary }
[Install phpSPA](installation.md){ .md-button }
[Use Template Project](template-project.md){ .md-button }
</div>

---

## 💡 Key Takeaways

!!! success "Remember These Concepts"
    
    1. **Components** are PHP functions that return HTML
    2. **State** creates reactive data with `createState()`
    3. **Routing** connects URLs to components
    4. **Performance** is optimized automatically
    5. **Security** is built-in by default

You're now ready to dive deeper into phpSPA development!
