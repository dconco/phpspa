# Setting the Page Title

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Each page in your application should have a unique and descriptive title. PhpSPA makes this incredibly easy by allowing you to chain a <code style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">->title()</code> method directly onto your page components.
</p>

!!! tip "Automatic Updates"
    When a user navigates to a new route, PhpSPA's client-side script will automatically update the browser tab's title for you.

---

## How to Use It

!!! example "Title Configuration"
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

!!! success "Result"
    Now, when a user is on the `/about` page, the browser tab will correctly display "**About Us - My Company**". It's a simple but crucial feature for building professional web applications.
