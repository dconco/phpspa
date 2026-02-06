## Performance: Asset Caching

To improve performance and reduce server load, you can instruct the user's browser to cache your CSS and JavaScript assets.

!!! info "Cache Control"
    Use the `->assetCacheHours()` method on your `$app` instance to set a cache duration. The library will automatically append a version query string to your asset URLs to handle cache-busting.

```php
<?php

use PhpSPA\App;

$app = new App($layout);

// Tell the browser to cache all CSS/JS assets for 24 hours.
$app->assetCacheHours(24);

// For no caching (useful in development), set it to 0.
$app->assetCacheHours(0);
```

!!! tip "Development vs Production"
    - **Development**: Set to `0` for no caching, so changes are immediately visible
    - **Production**: Set to `24` (or higher) for optimal performance and reduced server load

---

## Custom Cache Directory

!!! success "New in v2.0.8"
    Control where PhpSPA stores generated asset cache files.

By default, PhpSPA stores generated cache files in a `generated/` subdirectory next to each component. You can customize this location using `setGeneratedCacheDirectory()`:

```php
<?php

use PhpSPA\App;

$app = new App($layout);

// Store all cache files in a centralized directory
$app->setGeneratedCacheDirectory(__DIR__ . '/cache/phpspa');

// Or use system temp directory
$app->setGeneratedCacheDirectory(sys_get_temp_dir() . '/phpspa-cache');

```

### Benefits

- **Organization**: Keep cache files separate from source code
- **Performance**: Use faster storage (tmpfs, RAM disk) for cache
- **Management**: Easier to clear all cache files at once
- **Environment Control**: Different cache locations per environment
- **Docker/Container**: Store cache in volumes for persistence

### Default Behavior

Without calling `setGeneratedCacheDirectory()`, cache files are stored alongside components:

```
examples/components/
├── Counter.php
└── generated/
    ├── a3f8b2c1d5e9f7a4b2c8d1e6f9a3b7c2.generated.php
    └── d4e7a1b8c5f2e9d6a3b7c1f4e8a2d5b9.generated.php
```

With custom directory:

```
/cache/phpspa/
├── a3f8b2c1d5e9f7a4b2c8d1e6f9a3b7c2.generated.php
├── d4e7a1b8c5f2e9d6a3b7c1f4e8a2d5b9.generated.php
├── b7f4e2c9a1d8e5b3f6a9c2d4e7b1f8a3.generated.php
└── c8d5f1e4a7b2c9f6e3a1d7b4c8f2e5a9.generated.php
```

!!! info "Cache File Naming"
    Cache filenames are MD5 hashes of the original filename and file modification time, ensuring automatic invalidation when files change.

!!! warning "Directory Permissions"
    Ensure the specified directory exists and is writable by the web server:
    
    ```bash
    mkdir -p /var/cache/phpspa
    chmod 755 /var/cache/phpspa
    chown www-data:www-data /var/cache/phpspa
    ```
