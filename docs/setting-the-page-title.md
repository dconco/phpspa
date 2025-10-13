## Setting the Page Title

Each page in your application should have a unique and descriptive title. PhpSPA makes this incredibly easy by allowing you to chain a `->title()` method directly onto your page components.

When a user navigates to a new route, PhpSPA's client-side script will automatically update the browser tab's title for you.

### How to Use It

Simply add the `->title()` method when you are configuring your component's route.

```php
<?php
use phpSPA\App;
use phpSPA\Component;

// Assume our app and layout are set up
$app = new App($layout);

// --- Home Page Component ---
$homePage = new Component(function () {
   return '<h1>Welcome Home!</h1>';
});

// Set the title for the home page
$homePage->route('/')->title('My Awesome Homepage');


// --- About Page Component ---
$aboutPage = new Component(function () {
   return '<h1>About Our Company</h1>';
});

// Set the title for the about page
$aboutPage->route('/about')->title('About Us - My Company');


// Attach the components
$app->attach($homePage);
$app->attach($aboutPage);

$app->run();
```

Now, when a user is on the `/about` page, the browser tab will correctly display "**About Us - My Company**". It's a simple but crucial feature for building professional web applications.
