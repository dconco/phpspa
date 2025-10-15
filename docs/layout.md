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

## Setting the Default Target ID

By default, PhpSPA looks for an element with the ID `app` to render components. However, you can change this to any ID you prefer using the `defaultTargetID()` method.

```php
<?php

use PhpSPA\App;

$layout = require __DIR__ . '/layout.php';
$app = new App($layout);

// Set the default target ID for all components
$app->defaultTargetID('app'); // This is the default

// Or use a custom ID
$app->defaultTargetID('main-content');
```

!!! tip "Custom Target Element"
    If your layout uses a different ID like `<div id="main-content"></div>`, make sure to set it with `defaultTargetID()`.

### Example with Custom Target

```php
<?php
// Layout file with custom ID
return fn () => <<<HTML
   <!DOCTYPE html>
   <html>
      <head>
         <title>My App</title>
      </head>
      <body>
         <header>Site Header</header>
         <div id="main-content"></div>
         <footer>Site Footer</footer>
      </body>
   </html>
HTML;
```

```php
<?php
// index.php
use PhpSPA\App;

$layout = require __DIR__ . '/layout.php';
$app = new App($layout);

// Tell PhpSPA to render components inside #main-content
$app->defaultTargetID('main-content');

// ... attach components and run
$app->run();
```

!!! success "Flexible Layouts"
    This allows you to create complex layouts with headers, sidebars, and footers, while controlling exactly where your dynamic content renders.
