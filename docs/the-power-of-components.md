# The Power of Components

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
The core of PhpSPA is its component-based architecture. A component is a reusable piece of your UI, defined as a simple PHP function that returns an HTML string.
</p>

!!! magic "The Magic"
    The magic happens when you use these components like HTML tags **inside the `heredoc` string** of another component. This allows you to build complex pages by nesting smaller, reusable parts.

---

## Functional Components

!!! example "Simple Component"
    First, define a simple component.

```php
<?php
// A simple, reusable button component
function Button() {
   return <<<HTML
     <button>Click Me</button>
   HTML;
}
```

Now, use that `Button` component inside a main page component.

```php
<?php
function HomePage() {
   return <<<HTML
      <div class="container">
         <h1>Welcome!</h1>
         <p>Click the button below to continue.</p>

         <Button />
      </div>
   HTML;
}
```

---

## Handling Children and Props

!!! tip "True Power"
    Components become truly powerful when you pass data to them as props (attributes) and children (content inside the tags).

=== "Example Components with Props"

    ```php
    <?php
    use function Component\HTMLAttrInArrayToString;

    // Accepts children
    function Card($children) {
       return <<<HTML
          <div class="card">
             {$children}
          </div>
       HTML;
    }

    // Accepts props and children
    function LinkButton($to, $children, ...$attributes) {
       // Convert any extra HTML attributes into a string
       $attrString = HTMLAttrInArrayToString($attributes);

       return <<<HTML
         <a href="{$to}" {$attrString}>
           {$children}
         </a>
       HTML;
    }
    ```

=== "Composing Them in a Page"

    Now, let's use these components inside a `UserProfile` component. This shows how everything nests together.

    ```php
    <?php
    function UserProfile() {
       return <<<HTML
          <Card>
             <h2>User Dashboard</h2>
             <p>Welcome back! Here are your options:</p>

             <LinkButton to="/settings" class="btn btn-primary">
                Edit Settings
             </LinkButton>

             <LinkButton to="/logout" class="btn btn-secondary">
                Log Out
             </LinkButton>
          </Card>
       HTML;
    }
    ```

!!! success "Key Takeaway"
    You build your entire UI by composing these components. As long as the final output is one component attached to the `App` class, `PhpSPA` will handle rendering the entire nested tree.
