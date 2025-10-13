## Advanced Routing

PhpSPA's router is more than just static paths. It provides a powerful set of features for handling dynamic URLs, multiple routes, and specific HTTP methods, giving you full control over how your application responds to requests.

-----

### Handling URL Parameters

To capture dynamic segments from a URL, like a user's ID, you can define parameters in your route using curly braces `{}`. These parameters are then passed to your component in a special `$path` array.

```php
<?php
use phpSPA\Component;

$userProfile = new Component(function (array $path) {
   // The 'id' from the URL is available in the $path array
   $userId = $path['id'] ?? 'guest';

   return <<<HTML
      <h1>User Profile</h1>
      <p>You are viewing the profile for User ID: <strong>{$userId}</strong></p>
   HTML;
});

// This route will match URLs like /user/123, /user/456, etc.
$userProfile->route('/user/{id}');
```

-----

### Typed Parameters and Constraints

You can enforce specific data types and constraints directly in your route definition. This is great for validation and ensuring your component receives the correct type of data.

```php
<?php

// Only match if 'id' is an integer
$component->route('/post/{id: int}');

// Match if 'username' is alphanumeric
$component->route('/profile/{username: alnum}');

// Match an integer between 2 and 5 (inclusive)
$component->route('/rating/{value: int<2,5>}');

// Even complex nested types are supported
$component->route('/user/{username: string}/post/{id: int}');
```

-----

### Multiple Routes and HTTP Methods

You can assign multiple routes or HTTP methods to a single component.

  * **Multiple Routes:** Pass an array of paths to the `->route()` method.
  * **HTTP Methods:** Pass a pipe-separated string to the `->method()` method.

<!-- end list -->

```php
<?php
$dashboard = new Component(function () {
   return '<h1>Welcome to your Dashboard!</h1>';
});

// This component will render for both /home and /dashboard
$dashboard->route(['/home', '/dashboard']);

$contactForm = new Component(function (Request $request) {
   if ($request->isMethod('POST')) {
      // Handle form submission...
      return '<p>Thank you for your message!</p>';
   }
   // Show the form on GET request
   return '<form method="POST"></form>';
});

// This component responds to both GET and POST requests on the same URL
$contactForm->route('/contact')->method('GET|POST');
```
