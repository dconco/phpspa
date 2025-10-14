# Core Concepts: App & Component

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Everything in PhpSPA revolves around two main classes: <code style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">App</code> and <code style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">Component</code>.
</p>

<div class="grid cards" markdown>

-   :material-application: **App**
    
    ---
    
    This is the main container for your entire application. It holds your layout and manages all your components.

-   :material-cube: **Component**
    
    ---
    
    This is a reusable piece of your UI. Think of it as a simple PHP function that outputs HTML.

</div>

!!! tip "Simple Workflow"
    You create one `App` instance, create your `Component`s, and then attach them to the app.

---

## Here's how they work together:

```php
<?php

use PhpSPA\App;
use PhpSPA\Component;

// 1. Define a layout. It must have a `body()` function.
$Layout = function () {
   return body(); // This is where your component's content will be rendered.
};

// 2. Create the main application instance with your layout.
$app = new App($Layout);

// 3. Create a component. It's just a function that returns HTML.
$homePage = new Component(function () {
   return "<h1>Welcome to the Home Page!</h1>";
});

// 4. Configure the component (e.g., set its route).
$homePage->route('/');

// 5. Attach the component to the app.
$app->attach($homePage);

// 6. Run the application to render the page.
$app->run();
```
