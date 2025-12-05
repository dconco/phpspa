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

!!! note "Required Namespaces"
    All PhpSPA applications require these namespace imports:
    ```php
    <?php
    use PhpSPA\App;
    use PhpSPA\Component;
    ```
    Include these at the top of your PHP files.

---

## Here's how they work together:

**1. Define a Layout**

```php
<?php

$Layout = function () {
   return '<div id="app"></div>';
};
```

The layout defines where components will render.

**2. Create the App**

```php
<?php

$app = new App($Layout);
```

Initialize the main application with your layout.

**3. Build a Component**

```php
<?php

$homePage = new Component(function () {
   return "<h1>Welcome to the Home Page!</h1>";
});
```

Components are functions that return HTML.

**4. Set the Route**

```php
<?php

$homePage->route('/');
```

Configure which URL path triggers this component.

**5. Attach to App**

```php
<?php

$app->attach($homePage);
```

Register the component with the application.

**6. Run the Application**

```php
<?php

$app->run();
```

Render the page.

---

## Complete Example

```php
<?php

use PhpSPA\App;
use PhpSPA\Component;

$Layout = function () {
   return '<div id="app"></div>';
};

$app = new App($Layout);

$homePage = new Component(function () {
   return "<h1>Welcome to the Home Page!</h1>";
});

$homePage->route('/');
$app->attach($homePage);
$app->run();
```
