# CSRF Protection

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

Cross-Site Request Forgery (CSRF) is a common web security vulnerability. PhpSPA provides a simple and powerful way to protect your forms from these attacks, ensuring that submitted data comes from your actual site and not a malicious one.

!!! info "Security Layer"
    CSRF protection validates that form submissions originate from your site, not from external malicious sources.

## Protecting Forms with the `Csrf` Component

The easiest way to add CSRF protection is by using the built-in `<Component.Csrf />` component inside your forms.

You must give it a unique `name` prop for each form to prevent token conflicts.

```php
<?php
function ContactForm() {
   return <<<HTML
      <form method="POST" action="/contact">
         
         <Component.Csrf name="contact-form" />

         <input type="text" name="name" required>
         <textarea name="message" required></textarea>
         <button type="submit">Send Message</button>
      </form>
   HTML;
}
```

!!! tip "Unique Names"
    You must give it a unique `name` prop for each form to prevent token conflicts.

## Verifying the Token

On the server-side, you instantiate the `Csrf` class with the **same form name** and call the `verify()` method.

```php
<?php
use PhpSPA\Http\Request;
use Component\Csrf;

function handleContactSubmission(Request $request) {
   if ($request->isMethod('POST')) {
      
      // 1. Create a Csrf instance with the matching name.
      $csrf = new Csrf("contact-form");

      // 2. Verify the submitted token.
      if (!$csrf->verify()) {
         // Stop execution if the token is invalid or missing.
         http_response_code(403);
         die('Invalid CSRF token!');
      }

      // --- Token is valid, process the form data safely ---
      $name = $request('name');
      // ...
   }
}
```

!!! success "Token Reusability"
    By default, `verify()` consumes the token, so it can only be used once. To verify a token without expiring it, pass `false`:
    
    ```php
    if ($csrf->verify(false)) {
       // Token is valid and reusable.
    }
    ```
