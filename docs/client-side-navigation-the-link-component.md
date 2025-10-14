# Client-Side Navigation: The Link Component

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

To navigate between pages without a full browser reload, you must use the built-in `<Component.Link />`. This component looks like a standard `<a>` tag, but it's much smarter—it hooks into **PhpSPA's router** to fetch and render the new component on the fly, providing a seamless, SPA-like user experience. 🚀

!!! tip "Smart Navigation"
    The `<Component.Link />` component prevents full page reloads, giving your app that smooth, instant navigation feel.

## Basic Usage

The two main **props** are `to` for the destination URL and `children` for the link's text.

```php
<?php

function AppHeader() {
   return <<<HTML
      <header>
         <nav>
            <Component.Link to="/">Home</Component.Link>
            <Component.Link to="/about">About Us</Component.Link>
            <Component.Link to="/contact">Contact</Component.Link>
         </nav>
      </header>
   HTML;
}
```

!!! example "Navigation Example"
    The component automatically handles routing and state management for you.

## Passing Additional Attributes

You can pass any standard HTML attribute like `class` or `id` directly to the component, and they will be added to the final `<a>` tag.

```php
<?php

function AppFooter() {
   return <<<HTML
      <footer>
         <Component.Link to="/privacy-policy" class="footer-link" id="privacy-link">
            Privacy Policy
         </Component.Link>
      </footer>
   HTML;
}
```

This will render the following HTML:

```html
<a href="/privacy-policy" class="footer-link" id="privacy-link">
   Privacy Policy
</a>
```

!!! success "Best Practice"
    Always use `<Component.Link />` for navigating between your app's routes to get that smooth, instant page-load feel.
