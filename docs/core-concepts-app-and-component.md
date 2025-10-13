## Core Concepts: App & Component

Everything in PhpSPA revolves around two main classes: `App` and `Component`.

* `App`: This is the main container for your entire application. It holds your layout and manages all your components.
* `Component`: This is a reusable piece of your UI. Think of it as a simple PHP function that outputs HTML.

You create one `App` instance, create your `Component`s, and then attach them to the app.

### Here's how they work together:

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
