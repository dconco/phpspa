# Programmatic Navigation with `<Navigate />`

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

While `<Component.Link />` is perfect for user-driven navigation, you'll sometimes need to redirect a user automatically based on some logic (e.g., after a form submission). For this, use the `<Component.Navigate />` component.

!!! info "Automatic Redirects"
    When PhpSPA renders this component, it triggers an immediate, client-side redirect without a full page reload.

## Basic Usage

Simply render the component with a `path` prop. The redirect will happen as soon as the component is added to the page.

```php
<?php
use PhpSPA\Http\Request;

function LoginPage(Request $request) {
   $isLoggedIn = false;
   if ($request->isMethod('POST')) {
      // ... validate user credentials ...
      if ($credentialsAreValid) {
         $isLoggedIn = true;
      }
   }

   // Conditionally render the Navigate component or the login form
   if ($isLoggedIn) {
      return '<Component.Navigate path="/dashboard" />';
   }

   return <<<HTML
      <form method="POST">
         ...
      </form>
   HTML;
}
```

!!! example "Conditional Redirect"
    Perfect for post-login redirects or access control logic.

## Controlling Browser History

The `<Navigate />` component takes a `state` prop to control how it interacts with the browser's history.

=== "push (Default)"

    Adds a new entry to the browser's history. The user can click the "back" button to return to the previous page.

    ```php
    <Component.Navigate path="/dashboard" state="push" />
    ```

=== "replace"

    Replaces the current page in the browser's history. This is ideal for post-login redirects, as it prevents the user from clicking "back" to see the login form again.

    ```php
    <Component.Navigate path="/dashboard" state="replace" />
    ```

!!! tip "Post-Login Redirects"
    Use `state="replace"` to prevent users from navigating back to the login page.

```php
<?php
// Inside your component logic...

if ($isLoggedIn) {
   // Replace the login page in history so the user can't go back.
   return '<Component.Navigate path="/dashboard" state="replace" />';
}
```
