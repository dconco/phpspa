# Defining Routes

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Routing is how you tell PhpSPA which component to show for a specific URL. This is done with the <code style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">->route()</code> method.
</p>

!!! tip "Route Assignment"
    You create a component for each page of your site and assign it a unique URL path.

---

## Route Syntax

```php
<?php

$component->route('/path');
```

The `route()` method maps a URL path to a component. Use `/` for the home page, `/about` for an about page, etc.

---

## Example

!!! example "Simple Website"
    Let's create a simple website with a home page and an about page.

**1. Setup App**

```php
<?php

$layout = require __DIR__ . '/layout.php';
$app = new App($layout);
```

Load your layout and create the app instance.

**2. Create Home Page**

```php
<?php

$homePage = new Component(function () {
   echo '<h1>Welcome to the Home Page!</h1>';
});
$homePage->route('/');
```

Define the home page component and map it to `/`.

**3. Create About Page**

```php
<?php

$aboutPage = new Component(function () {
   echo '<h1>About Our Company</h1>';
});
$aboutPage->route('/about');
```

Define the about page component and map it to `/about`.

**4. Attach and Run**

```php
<?php

$app->attach($homePage);
$app->attach($aboutPage);
$app->run();
```

Register components and render the app.

---

### Complete Example

```php
<?php

use PhpSPA\App;
use PhpSPA\Component;

// Assume our layout is loaded
$layout = require __DIR__ . '/layout.php';
$app = new App($layout);

// --- Create the Home Page Component ---
$homePage = new Component(function () {
   echo '<h1>Welcome to the Home Page!</h1>';
});
$homePage->route('/'); // Maps this component to the root URL

// --- Create the About Page Component ---
$aboutPage = new Component(function () {
   echo '<h1>About Our Company</h1>';
});
$aboutPage->route('/about'); // Maps this component to the /about URL

// Attach both components to the app
$app->attach($homePage);
$app->attach($aboutPage);

// The router will now render the correct component
$app->run();
```

!!! success "That's it"
    When a user visits your site at `/about`, PhpSPA will automatically render the `$aboutPage` component.
