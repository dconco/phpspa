# API Authentication

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

PhpSPA isn't just for rendering HTML. You can easily create secure API endpoints by creating components that return JSON. The `Request` object has built-in helpers to make checking for authentication credentials simple and clean.

!!! info "Secure APIs"
    This is perfect for when your frontend needs to fetch data from a secure source.

## API Key Authentication

A common method for securing an API is to require an API key in the request headers. The `$request->apiKey()` method makes this easy to check.

By default, it looks for the key in the `Api-Key` header.

```php
<?php
use PhpSPA\Component;
use PhpSPA\Http\Request;

$userDataApi = new Component(function (Request $request) {
   // 1. Validate the API key.
   // Replace 'YOUR_SECRET_KEY' with your actual key.
   if (!$request->apiKey('YOUR_SECRET_KEY')) {
      http_response_code(401); // Unauthorized
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Invalid API Key']);
      return;
   }

   // 2. If the key is valid, return the data.
   $data = ['id' => 123, 'name' => 'John Doe', 'email' => 'john.doe@example.com'];
   header('Content-Type: application/json');
   return json_encode($data);
});

$userDataApi->route('/api/user');
```

!!! tip "Default Header"
    By default, it looks for the key in the `Api-Key` header.

## HTTP Basic & Bearer Token Authentication

For more standard authentication methods, the `$request->auth()` method is your go-to tool. It automatically parses the `Authorization` header and gives you access to both **Basic** and **Bearer** token credentials.

```php
<?php
use PhpSPA\Component;
use PhpSPA\Http\Request;

$secureDataApi = new Component(function (Request $request) {
   $auth = $request->auth();

   // Check for a Bearer token (commonly used with JWTs)
   if ($auth->bearer) {
      // ... validate the Bearer token ...
      if (isValidToken($auth->bearer)) {
         echo json_encode(['data' => 'This is your secure data.']);
         return;
      }
   }

   // Check for Basic auth credentials
   if ($auth->basic) {
      // $auth->basic is an object with 'user' and 'password' properties
      if ($auth->basic->user === 'admin' && $auth->basic->password === 'secret') {
         echo json_encode(['data' => 'Authenticated via Basic Auth.']);
         return;
      }
   }

   // If no valid auth is found, deny access.
   http_response_code(401);
   echo json_encode(['error' => 'Authentication required.']);
});

$secureDataApi->route('/api/secure-data');
```

!!! success "Authentication Methods"
    Support both Bearer tokens (commonly used with JWTs) and HTTP Basic authentication with a single method.
