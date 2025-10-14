# Security: Content Security Policy (CSP)

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

To protect your application from Cross-Site Scripting (XSS) attacks, PhpSPA includes a simple helper for implementing a **nonce-based Content Security Policy**. This ensures that only the inline scripts and styles you've authorized can be executed by the browser.

!!! info "XSS Protection"
    CSP helps prevent malicious scripts from executing in your application.

## Step 1: Enable CSP

In your application's entry point (e.g., `index.php`), call `Nonce::enable()` **before any output is sent**. This will automatically generate a unique, secure nonce for each request and send the appropriate CSP header.

```php
<?php
use PhpSPA\Http\Security\Nonce;
use PhpSPA\App;

// Enable a strict CSP policy before creating your app
Nonce::enable();

// You can also customize the allowed sources
Nonce::enable([
   'script-src' => ["https://cdn.jsdelivr.net"], // By default, 'self' is allowed
   'style-src'  => ["https://fonts.googleapis.com"], // By default, 'self' is allowed
]);

// Now, create your app as usual
$app = new App($layout);
// ...
```

!!! tip "Custom Sources"
    You can also customize the allowed sources for scripts and styles.

## Step 2: Apply the Nonce to Your Tags

Now, you must add the generated nonce attribute to **every inline `<script>` and `<style>` tag** in your application. The `Nonce::attr()` method generates the full HTML attribute string for you (e.g., `nonce="a1b2c3d4"`).

This is typically done in your main Layout file.

```php
<?php
use PhpSPA\Http\Security\Nonce;

function Layout() {
   // Get the nonce attribute string
   $nonce = Nonce::attr();

   return <<<HTML
      <!DOCTYPE html>
      <html>
      <head>
         <title>My Secure App</title>

         <!-- Apply the nonce to the style tag -->
         <style {$nonce}>
            body { font-family: sans-serif; }
         </style>
      </head>
      <body>
         <div id="app"></div>

         <!-- Apply the nonce to the script tag -->
         <script {$nonce}>
            console.log('This script is allowed to run!');
         </script>
      </body>
      </html>
   HTML;
}
```

!!! success "Security Hardened"
    By following these two steps, you significantly harden your application's security against injection attacks. 👍
    
    - Use `Nonce::nonce()` to get just the nonce value if you need it for other purposes
    - Call `Nonce::disable()` to turn off CSP maybe for another new Application instance
