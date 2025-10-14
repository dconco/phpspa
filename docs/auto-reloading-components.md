# Auto-Reloading Components 🔄

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

For components that display live, frequently changing data—like a news ticker, a dashboard chart, or a notification feed—you can instruct PhpSPA to automatically refresh them at a set interval.

!!! info "Efficient Updates"
    This is done by chaining the `->reload()` method to your component. This method is highly efficient, as it only re-fetches and updates the specific component, not the entire page.

## How to Use It

The `->reload()` method accepts one argument: the refresh interval in **milliseconds**.

Here is an example of a live clock component that updates itself every second.

```php
<?php
use PhpSPA\App;
use PhpSPA\Component;

// Assume our app and layout are set up
$app = new App($layout);

// --- Live Clock Component ---
$liveClock = new Component(function () {
   // This PHP code runs on the server every time the component is fetched
   $currentTime = date('H:i:s');
   return "<h2>Current Server Time: {$currentTime}</h2>";
});

// Configure the component to reload every 1000 milliseconds (1 second)
$liveClock
   ->route('/clock')
   ->title('Live Clock')
   ->reload(1000);

// Attach the component
$app->attach($liveClock);

$app->run();
```

!!! tip "Live Clock Example"
    Here is an example of a live clock component that updates itself every second.

!!! success "Automatic Updates"
    When a user visits the `/clock` page, PhpSPA's JavaScript runtime will see the reload instruction and automatically request a fresh version of the `liveClock` component every second, keeping the time perfectly up-to-date without any full page reloads.
