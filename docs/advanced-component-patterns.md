# Advanced Component Patterns

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Beyond simple functions, PhpSPA offers several powerful ways to structure and call your components, giving you flexibility for any project architecture.
</p>

---

## Class-Based Components

!!! info "Complex Components"
    For more complex components that might have their own methods or properties, you can use a class. If a class has a `__render()` method, `PhpSPA` will automatically call it when the class is used as a component. Props and children are passed as arguments to the `__render` method.

```php
<?php
class Alert {
   // This method is called automatically
   public function __render($type, $children) {
      $class = "alert alert-{$type}"; // e.g., 'alert alert-success'

      return <<<HTML
         <div class="{$class}">
            {$children}
         </div>
      HTML;
   }
}

// How to use it:
function StatusMessage() {
   return <<<HTML
      <Alert type="success">
         <strong>Success!</strong> Your profile has been updated.
      </Alert>
   HTML;
}
```

!!! tip "Method Components"
    You can even call other public methods of a class directly as components using the `::` syntax.

    ```php
    <MyComponent::Header />
    <MyComponent::Footer />
    ```

---

## Namespaced and Variable Components

!!! example "Flexible Organization"
    PhpSPA makes it easy to organize and use components from different parts of your codebase.

=== "Namespaced Components"

    If your component function is in a namespace, use a dot (`.`) instead of a backslash (`\`) to call it.

    ```php
    <?php
    // Assuming Button is in the UI\Elements namespace
    function SomePage() {
       return <<<HTML
          <UI.Elements.Button />
       HTML;
    }
    ```

=== "Callable Variable Components"

    You can assign a component to a variable and use it directly. This is great for dynamic components or for keeping your template logic clean. To use a variable component, prefix its name with `@` or `$` (if using `$`, escape it within `heredoc` strings: `<\$Link />`).

    ```php
    <?php
    function Navigation() {
       // Define a component as a callable variable
       $NavLink = fn ($to, $children) => <<<HTML
          <a href="{$to}" class="nav-link">{$children}</a>
       HTML;

       // Export the variable to make it available in the heredoc scope
       scope(compact('NavLink'));

       // Use the variable component with the @ prefix
       return <<<HTML
          <nav>
             <@NavLink to="/">Home</@NavLink>
             <@NavLink to="/about">About</@NavLink>

             <!-- Or using the $ prefix (escaped) -->
             <\$NavLink to="/contact">Contact</\$NavLink>
          </nav>
       HTML;
    }
    ```
