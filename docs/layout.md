## The Layout

Your Layout is the main HTML shell that wraps your entire application. It's where you define your `<html>` document, the `<head>` section, and any global scripts or styles.

A layout has a simple rule:

1.  **Provide a target element.** This is where your components will be rendered. A common & default choice is `<div id="app"></div>`.

### Example Layout (`/layout.php`):

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

You then pass this layout directly to your `App` when you create it.

```php
<?php

use PhpSPA\App;

// Load the layout file
$layout = require __DIR__ . '/layout.php';

// Initialize the app with the layout
$app = new App($layout);
```

Now, all components attached to your app will be rendered inside that `<div id="app"></div>`.
