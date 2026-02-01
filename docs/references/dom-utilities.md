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
