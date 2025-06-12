# 🧱 Layout and Content Swap Mechanism

At the heart of phpSPA is this:
**One layout. Swappable content. Smooth experience.**

The idea is simple: you define your base HTML layout once, and phpSPA will dynamically update just the main area (without full page reloads) whenever users navigate around.

---

## 🏗️ Define Your Layout

Your layout is the foundation of your entire application. It's a function that returns HTML, typically using PHP's heredoc syntax for clean, readable markup:

=== "Basic Layout"

    ```php
    <?php

    function layout() {
        return <<<HTML
        <html>
            <head>
                <title>My App</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body>
                <nav>
                    <a href="/" data-type="phpspa-link-tag">Home</a>
                    <a href="/about" data-type="phpspa-link-tag">About</a>
                    <a href="/contact" data-type="phpspa-link-tag">Contact</a>
                </nav>
                <main>
                    __CONTENT__ <!-- (1) -->
                </main>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
        HTML;
    }
    ```

    1. The magic placeholder where dynamic content gets inserted

=== "Advanced Layout"

    ```php
    <?php

    function layout() {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>phpSPA Application</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="/assets/styles.css">
                <link rel="icon" href="/favicon.ico">
            </head>
            <body>
                <header class="app-header">
                    <div class="container">
                        <h1 class="logo">My App</h1>
                        <nav class="main-nav">
                            <a href="/" data-type="phpspa-link-tag">🏠 Home</a>
                            <a href="/dashboard" data-type="phpspa-link-tag">📊 Dashboard</a>
                            <a href="/users" data-type="phpspa-link-tag">👥 Users</a>
                            <a href="/settings" data-type="phpspa-link-tag">⚙️ Settings</a>
                        </nav>
                    </div>
                </header>
                
                <main id="app-content" class="main-content">
                    __CONTENT__ <!-- (1) -->
                </main>
                
                <footer class="app-footer">
                    <p>&copy; 2025 My Application</p>
                </footer>
                
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
                <script src="/assets/app.js"></script>
            </body>
        </html>
        HTML;
    }
    ```

    1. Content placeholder with semantic HTML structure

=== "Layout with Sidebar"

    ```php
    <?php

    function layout() {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>phpSPA Admin</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="/assets/admin.css">
            </head>
            <body class="admin-layout">
                <aside class="sidebar">
                    <div class="sidebar-header">
                        <h2>Admin Panel</h2>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="/admin" data-type="phpspa-link-tag">📊 Dashboard</a>
                        <a href="/admin/users" data-type="phpspa-link-tag">👥 Users</a>
                        <a href="/admin/posts" data-type="phpspa-link-tag">📝 Posts</a>
                        <a href="/admin/settings" data-type="phpspa-link-tag">⚙️ Settings</a>
                    </nav>
                </aside>
                
                <div class="main-wrapper">
                    <header class="top-bar">
                        <button class="menu-toggle">☰</button>
                        <div class="user-menu">
                            <span>Welcome, Admin</span>
                            <a href="/logout">Logout</a>
                        </div>
                    </header>
                    
                    <main class="content-area">
                        __CONTENT__ <!-- (1) -->
                    </main>
                </div>
                
                <script src="https://cdn.example.com/phpspa.js"></script>
            </body>
        </html>
        HTML;
    }
    ```

    1. Content swaps within the admin layout structure

!!! info "Layout Function Key Points"
    The special string `__CONTENT__` is where the active component will be inserted by phpSPA. This placeholder is **required** and acts as the injection point for dynamic content.

!!! tip "Layout Best Practices"
    - 🎨 **Keep styles consistent** - Define global CSS in your layout
    - 🔗 **Use `data-type="phpspa-link-tag"` attributes** - Enable SPA navigation on links
    - 📱 **Include viewport meta** - Ensure mobile responsiveness
    - ⚡ **Load phpSPA script** - Include the library for dynamic functionality
    - 🏷️ **Semantic HTML** - Use proper HTML5 elements for better accessibility

---

## 🚀 Setting Up the App

Once you've defined your layout function, initialize your phpSPA application:

=== "Basic App Setup"

    ```php
    use phpSPA\App;

    $app = new App('layout'); // (1)
    ```

    1. Pass the layout function name as a string (callable)

=== "Alternative Setup Methods"

    ```php
    <?php
    use phpSPA\App;

    // Method 1: Function name as string
    $app = new App('layout');

    // Method 2: Anonymous function
    $app = new App(function() {
        return <<<HTML
        <html>
            <body>
                <main>__CONTENT__</main>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
        HTML;
    });

    // Method 3: Class method
    class LayoutProvider {
        public static function getLayout() {
            return <<<HTML
            <!-- Your layout HTML -->
            HTML;
        }
    }
    $app = new App([LayoutProvider::class, 'getLayout']);
    ```

=== "Configuration Options"

    ```php
    <?php
    use phpSPA\App;

    $app = new App('layout');

    // Configure default settings
    $app->defaultTargetID("main-content"); // (1)
    $app->setBaseURL("/app"); // (2)
    $app->enableDebugMode(true); // (3)
    ```

    1. Set default DOM target for content swapping
    2. Configure base URL for routing
    3. Enable debug mode for development

!!! warning "Callable Requirements"
    `layout` must be a **callable** — the function itself, not its output. Pass the function name as a string, not the result of calling the function.

---

## 🎯 Default Target ID Configuration

By default, phpSPA uses the `__CONTENT__` placeholder for initial renders. For dynamic navigations handled via JavaScript, you can specify which DOM element should be replaced:

=== "Setting Default Target"

    ```php
    $app->defaultTargetID("main"); // (1)
    ```

    1. JavaScript will replace content inside the `<main>` element

=== "Multiple Target Examples"

    ```php
    // Target by ID
    $app->defaultTargetID("app-content");

    // Target by class (first match)
    $app->defaultTargetID(".content-area");

    // Target by element type
    $app->defaultTargetID("main");
    ```

=== "Component-Specific Targets"

    ```php
    <?php
    // You can override per component
    $dashboard = new Component('Dashboard');
    $dashboard->route("/dashboard");
    $dashboard->targetID("dashboard-area"); // (1)

    $sidebar = new Component('Sidebar');
    $sidebar->route("/sidebar");
    $sidebar->targetID("sidebar-content"); // (2)
    ```

    1. Dashboard content goes to `#dashboard-area`
    2. Sidebar content goes to `#sidebar-content`

!!! tip "Target ID Best Practices"
    - 🎯 **Use semantic IDs** - `main-content`, `sidebar-area`, `dashboard-panel`
    - 🔄 **Consistent targeting** - Keep the same target for related components
    - 📏 **Specific selectors** - Use IDs rather than classes for reliability
    - 🎨 **CSS-friendly names** - Avoid spaces and special characters

---

## 📦 Complete App Setup Example

Here's a comprehensive example showing how to set up a complete phpSPA application:

=== "Full Application"

    ```php
    <?php
    require_once 'vendor/autoload.php';

    use phpSPA\App;
    use phpSPA\Component;

    // Define the layout function
    function layout() {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>My phpSPA App</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="/assets/app.css">
            </head>
            <body>
                <header>
                    <nav>
                        <a href="/" data-type="phpspa-link-tag">Home</a>
                        <a href="/about" data-type="phpspa-link-tag">About</a>
                        <a href="/contact" data-type="phpspa-link-tag">Contact</a>
                    </nav>
                </header>
                <main id="content">
                    __CONTENT__
                </main>
                <script src="https://cdn.example.com/phpspa.js"></script>
            </body>
        </html>
        HTML;
    }

    // Component functions
    function Home() {
        return <<<HTML
        <div class="home-page">
            <h1>Welcome to My App! 🎉</h1>
            <p>This is the home page content.</p>
            <Link to="/about" label="Learn More" class="btn" />
        </div>
        HTML;
    }

    function About() {
        return <<<HTML
        <div class="about-page">
            <h1>About Us 📖</h1>
            <p>Learn more about our amazing application.</p>
        </div>
        HTML;
    }

    function Contact() {
        return <<<HTML
        <div class="contact-page">
            <h1>Contact Us 📧</h1>
            <form>
                <input type="email" placeholder="Your email" required>
                <textarea placeholder="Your message" required></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>
        HTML;
    }

    // Initialize the app
    $app = new App('layout');
    $app->defaultTargetID("content");

    // Create and register components
    $home = new Component('Home');
    $home->route("/");
    $home->title("Home - My App");

    $about = new Component('About');
    $about->route("/about");
    $about->title("About - My App");

    $contact = new Component('Contact');
    $contact->route("/contact");
    $contact->title("Contact - My App");

    // Attach components to the app
    $app->attach($home);
    $app->attach($about);
    $app->attach($contact);

    // Run the application
    $app->run();
    ?>
    ```

=== "Component Classes"

    ```php
    <?php
    // Alternative: Using component classes
    class HomeComponent {
        public function render() {
            return <<<HTML
            <div class="home-page">
                <h1>Welcome! 🏠</h1>
                <p>This is rendered by a component class.</p>
            </div>
            HTML;
        }
    }

    // Register class-based component
    $home = new Component([new HomeComponent(), 'render']);
    $home->route("/");
    $app->attach($home);
    ```

---

## 🔀 How Content Swap Works

Understanding the content swap mechanism helps you build better phpSPA applications:

!!! info "Content Swap Flow"

    **1. Initial Page Load** 🌱
    :   User visits `/` → phpSPA renders layout + Home component at `__CONTENT__`
    
    **2. Navigation Intercept** 🔗  
    :   User clicks SPA link → JavaScript intercepts the click event
    
    **3. Background Request** 📡
    :   JS requests just the new component HTML from the server
    
    **4. Dynamic Update** ⚡
    :   phpSPA updates the target area (e.g., `<main>`) without touching the rest of the page

=== "Step-by-Step Breakdown"

    ```mermaid
    sequenceDiagram
        participant User
        participant Browser
        participant phpSPA_JS
        participant Server
        
        User->>Browser: Clicks SPA link
        Browser->>phpSPA_JS: Link click intercepted
        phpSPA_JS->>Server: Fetch component HTML
        Server->>phpSPA_JS: Return component content
        phpSPA_JS->>Browser: Update target DOM element
        Browser->>User: Show new content instantly
    ```

=== "Technical Details"

    1. Prevent default page navigation
    2. Special header tells server this is an SPA request
    3. Update only the target content area
    4. Update browser URL without reload

!!! success "Benefits of Content Swapping"

    **⚡ Lightning Fast**
    :   No full page reloads - only content changes
    
    **🎨 Consistent UI**
    :   Layout, navigation, and styles remain intact
    
    **📱 Mobile Friendly**
    :   Reduced data usage and faster interactions
    
    **🔄 Smooth Transitions**
    :   Enable CSS animations between content changes
    
    **🎯 Focused Updates**
    :   Update only what needs to change

---

## 💡 Advanced Layout Techniques

=== "Conditional Content"

    ```php
    <?php
    function layout() {
        $isAdmin = $_SESSION['user_role'] === 'admin';
        
        return <<<HTML
        <html>
            <body>
                <nav>
                    <a href="/" data-type="phpspa-link-tag">Home</a>
                    <?php if ($isAdmin): ?>
                        <a href="/admin" data-type="phpspa-link-tag">Admin</a>
                    <?php endif; ?>
                </nav>
                <main>__CONTENT__</main>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
        HTML;
    }
    ```

=== "Dynamic Meta Tags"

    ```php
    <?php
    function layout() {
        return <<<HTML
        <html>
            <head>
                <title>My App</title>
                <meta name="description" content="Default description">
                <meta charset="UTF-8">
            </head>
            <body>
                <main>__CONTENT__</main>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </body>
        </html>
        HTML;
    }
    ```

=== "Multi-Layout Support"

    ```php
    <?php
    function getLayout($type = 'default') {
        switch ($type) {
            case 'admin':
                return adminLayout();
            case 'auth':
                return authLayout();
            default:
                return defaultLayout();
        }
    }

    // Use different layouts
    $app = new App(function() {
        return getLayout($_GET['layout'] ?? 'default');
    });
    ```

---

## 🎯 Key Takeaways

!!! quote "Layout & Content Swap Summary"
    - 🏗️ **Define layout once** - No need to duplicate markup across pages
    - 🔄 **`__CONTENT__` placeholder** - Gets automatically replaced with component content  
    - 🎯 **Target ID control** - Specify exactly where content should be updated
    - ⚡ **Smooth navigation** - JavaScript handles transitions without page reloads
    - 🎨 **Consistent experience** - Layout stays constant while content changes dynamically

---

<div class="phpspa-nav">
    <div class="nav-section">
        <div class="nav-previous">
            <a href="/6-loading-events/" class="nav-link">
                <span class="nav-direction">← Previous</span>
                <span class="nav-title">Loading Event Hooks</span>
                <span class="nav-description">Master loading states and user feedback</span>
            </a>
        </div>

        <div class="nav-next">
            <a href="/8-component-rendering-and-target-areas/" class="nav-link featured">
                <span class="nav-direction">Next →</span>
                <span class="nav-title">Component Rendering & Targets</span>
                <span class="nav-description">Deep dive into component rendering and target areas</span>
                <span class="nav-badge">Core Concept</span>
            </a>
        </div>
    </div>
    
    <div class="nav-progress">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 70%;"></div>
        </div>
        <span class="progress-text">Chapter 7 of 10 Complete</span>
    </div>
</div>

<style>
.phpspa-nav {
    margin: 3rem 0 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.nav-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.nav-link {
    display: block;
    padding: 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-previous .nav-link {
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
    border: 1px solid #cbd5e1;
    color: #475569;
}

.nav-previous .nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #94a3b8;
}

.nav-next .nav-link {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    border: 1px solid #6d28d9;
    color: white;
    position: relative;
}

.nav-next .nav-link.featured::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

.nav-next .nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(124, 58, 237, 0.4);
    background: linear-gradient(135deg, #6d28d9 0%, #4c1d95 100%);
}

.nav-direction {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.nav-title {
    display: block;
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.nav-description {
    display: block;
    font-size: 0.875rem;
    opacity: 0.7;
    line-height: 1.4;
}

.nav-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.nav-progress {
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #7c3aed 0%, #06b6d4 100%);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-section {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .phpspa-nav {
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .nav-link {
        padding: 1.25rem;
    }

    .nav-badge {
        position: static;
        display: inline-block;
        margin-top: 0.5rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .phpspa-nav {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-color: #334155;
    }

    .nav-previous .nav-link {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        border-color: #475569;
        color: #e2e8f0;
    }

    .nav-previous .nav-link:hover {
        border-color: #64748b;
    }

    .progress-bar {
        background: #334155;
    }

    .progress-text {
        color: #94a3b8;
    }
}
</style>
