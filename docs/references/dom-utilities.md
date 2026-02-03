# DOM Utilities

!!! success "New in v2.0.4"
    :material-new-box: **DOM utilities** for dynamic page manipulation

---

## `DOM::Title()`

Get or set the page title dynamically from any component.

### Usage

```php
<?php
use PhpSPA\DOM;

// Set title
DOM::Title('User Profile - My App');

// Get title
$currentTitle = DOM::Title();
```

### Dynamic Example

```php
<?php
use PhpSPA\Component;
use PhpSPA\DOM;

$userProfile = (new Component(function(array $path) {
   $username = $path['username'];

   // Set dynamic title based on data
   DOM::Title("{$username} - Profile");

   return "<h1>{$username}</h1>";
}))->route('/user/{username: string}');
```

!!! info "Persistence"
    The title persists across component navigations until explicitly changed by another component or a page reload.

---

## `DOM::meta()`

Set or override meta tags dynamically from inside any component. This allows you to update SEO, Open Graph, or other meta tags at runtime, per request or per route.

### Usage

```php
<?php
use PhpSPA\DOM;

// Set a meta tag (e.g., description)
DOM::meta(name: 'description', content: 'Dynamic page description');

// Set Open Graph or custom meta
DOM::meta(property: 'og:title', content: 'Dynamic OG Title');
```

- Component meta overrides App meta.
- `DOM::meta()` overrides both App and component meta for the current request.
- You can call `DOM::meta()` multiple times to set or update different tags.

### Example

```php
<?php
use PhpSPA\Component;
use PhpSPA\DOM;

$home = (new Component(function() {
   DOM::meta(name: 'description', content: 'Welcome to the homepage!');
   DOM::meta(property: 'og:title', content: 'Home - My App');
   return '<h1>Home</h1>';
}))->route('/');
```

!!! info "Override Order"
    App meta < Component meta < DOM::meta() (highest priority)

---

## `DOM::link()`

!!! success "New in v2.0.8"
    Set or override link tags dynamically from inside any component

Set link tags dynamically at runtime to override global `App::link()` or component-level `Component::link()` declarations. Perfect for loading stylesheets, preload assets, or other link tags conditionally.

### Usage

```php
<?php
use PhpSPA\DOM;

// Add a stylesheet dynamically
DOM::link('/assets/dark-theme.css', 'dark-theme', 'text/css', 'stylesheet');

// Add a preload link
DOM::link('/fonts/custom.woff2', 'custom-font', 'font/woff2', 'preload', [
    'as' => 'font',
    'crossorigin' => 'anonymous'
]);

// Add favicon
DOM::link('/favicon.ico', 'favicon', 'image/x-icon', 'icon');
```

### Dynamic Theming Example

```php
<?php
use PhpSPA\Component;
use PhpSPA\DOM;
use PhpSPA\Http\Request;

$themeComponent = (new Component(function(Request $request) {
   // Check user preference
   $theme = $request->cookie('theme') ?? 'light';
   
   // Override stylesheet based on preference
   if ($theme === 'dark') {
      DOM::link('/assets/dark-theme.css', 'theme', 'text/css', 'stylesheet');
   } else {
      DOM::link('/assets/light-theme.css', 'theme', 'text/css', 'stylesheet');
   }
   
   return '<div>Theme applied!</div>';
}))->route('/');
```

### Parameters

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `$content` | `callable\|string` | Callable returning link tag HTML, or direct path/URL |
| `$name` | `?string` | Optional name for override identification |
| `$type` | `?string` | MIME type (e.g., 'text/css', 'image/x-icon') |
| `$rel` | `?string` | Relationship attribute (default: 'stylesheet') |
| `$attributes` | `array` | Additional attributes (e.g., `['crossorigin' => 'anonymous']`) |

### Override Behavior

Links are overridden based on:

1. **Name match** - If `$name` is provided and matches existing link
2. **href match** - If content is a direct path/URL and matches existing link
3. **rel+type match** - For unnamed links with same rel and type combination

!!! info "Override Order"
    App::link() < Component::link() < DOM::link() (highest priority)

### Getting All Links

```php
<?php
// Retrieve all dynamically set links
$links = DOM::link();

foreach ($links as $link) {
    echo "Name: {$link['name']}, Rel: {$link['rel']}\n";
}
```
