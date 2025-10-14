# The Layout

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Your Layout is the main HTML shell that wraps your entire application. It's where you define your <code>&lt;html&gt;</code> document, the <code>&lt;head&gt;</code> section, and any global scripts or styles.
</p>

!!! info "Simple Rule"
    A layout has a simple rule:
    
    **Provide a target element.** This is where your components will be rendered. A common & default choice is `<div id="app"></div>`.

---

## Example Layout (`/layout.php`)

!!! example "Layout Structure"
    This file simply returns a function that outputs your HTML structure.

```php
<?php

return fn () => <<<HTML
   <!DOCTYPE html>
   <html>
      <head>
         <title>My Awesome App</title>
      </head>
      <body>
         <div id="app"></div>
      </body>
   </html>
HTML;
```

---

## Using the Layout

You then pass this layout directly to your `App` when you create it.

```php
<?php

use PhpSPA\App;

// Load the layout file
$layout = require __DIR__ . '/layout.php';

// Initialize the app with the layout
$app = new App($layout);
```

!!! success "Result"
    Now, all components attached to your app will be rendered inside that `<div id="app"></div>`.
