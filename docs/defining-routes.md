## Defining Routes

Routing is how you tell PhpSPA which component to show for a specific URL. This is done with the `->route()` method.

You create a component for each page of your site and assign it a unique URL path.

### Example:

Let's create a simple website with a home page and an about page.

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

That's it. When a user visits your site at `/about`, PhpSPA will automatically render the `$aboutPage` component.
