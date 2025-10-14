## Performance: Asset Caching

To improve performance and reduce server load, you can instruct the user's browser to cache your CSS and JavaScript assets.

!!! info "Cache Control"
    Use the `->assetCacheHours()` method on your `$app` instance to set a cache duration. The library will automatically append a version query string to your asset URLs to handle cache-busting.

```php
<?php
use phpSPA\App;

$app = new App($layout);

// Tell the browser to cache all CSS/JS assets for 24 hours.
$app->assetCacheHours(24);

// For no caching (useful in development), set it to 0.
$app->assetCacheHours(0);
```

!!! tip "Development vs Production"
    - **Development**: Set to `0` for no caching, so changes are immediately visible
    - **Production**: Set to `24` (or higher) for optimal performance and reduced server load
