## Handling Requests & Sessions

Your components often need to process incoming data, like form submissions or API calls. PhpSPA provides a clean, object-oriented way to access request data.

When a component is rendered, it can receive the current `Request` object as an argument.

### Accessing Request Data

The `Request` object is a powerful wrapper around PHP's superglobals (`$_POST`, `$_GET`, `$_FILES`, etc.), making your code cleaner and more secure.

Here's how you would handle a form submission:

```php
<?php

use PhpSPA\Component;
use PhpSPA\Http\Request;

$loginPage = new Component(function (Request $request) {
   // Check if the form was submitted
   if ($request->isMethod('POST')) {
      $email = $request('email');
      $password = $request('password');

      // ... process login logic ...
   }

   // Display the login form
   echo <<<HTML
      <form method="POST">
         <input type="email" name="email" placeholder="Email">
         <input type="password" name="password" placeholder="Password">
         <button type="submit">Log In</button>
      </form>
   HTML;
});

$loginPage->route('/login')->method('GET|POST');
```

The `Request` object has many other useful methods:

  * `$request->get('key')`: Get a URL query parameter.
  * `$request->json('key')`: Get data from a JSON request body.
  * `$request->files('avatar')`: Access uploaded file data.
  * `$request->header('Authorization')`: Read a request header.
  * `$request->ip()`: Get the client's IP address.
  * `$request->isAjax()`: Check if it's an AJAX request.

### Redirects & Session Management

`PhpSPA` also includes helpers for common actions.

  * **Redirecting:** Use the global `Redirect()` function to send the user to a new page.
  * **Sessions:** Use the static `Session` class to manage user data across requests.

<!-- end list -->

```php
<?php

use PhpSPA\Http\Request;
use PhpSPA\Http\Session;
use function PhpSPA\Http\Redirect;

function handleLogin(Request $request) {
   $email = $request->post('email');
   // ... validate user ...

   if ($isValid) {
      Session::set('user_id', 123); // Log the user in
      Redirect('/dashboard');      // Send them to the dashboard
   }
}
```

### Content Security Policy (CSP)

For added security, you can easily enable a nonce-based Content Security Policy to protect against XSS attacks.

```php
<?php
use PhpSPA\Http\Security\Nonce;

// Enable CSP with a strict policy
Nonce::enable();

$nonce = Nonce::attr();

// In your layout, use the nonce attribute for your scripts
echo <<<HTML
   <script $nonce>
      /* ... */
   </script>
HTML;
```
