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
