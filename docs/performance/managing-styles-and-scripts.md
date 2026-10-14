# Managing Styles & Scripts 🎨

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

PhpSPA offers a powerful way to manage your CSS and JavaScript. You can write them directly in PHP, and the library will automatically serve them as optimized, cache-busting external files for maximum performance.

!!! info "Asset Management"
    PhpSPA automatically generates versioned, cacheable asset files for optimal performance.

## Global Assets

For styles and scripts that apply to your entire site, attach them directly to your `$app` instance.

The methods `styleSheet()` and `script()` accept a callable that returns your raw code. For better organization, it's recommended to keep this code in separate files.

**Example (`styles/GlobalStyle.php`):**

```php
<?php
// returns a callable with your CSS code
return fn () => <<<CSS
   body {
      background-color: #f8f9fa;
      font-family: sans-serif;
   }
CSS;
```

**Example (`index.php`):**

```php
<?php
$app = new App($layout);

// Attach the global style and script from their files
$app->styleSheet(require 'styles/GlobalStyle.php', 'global-styles');
$app->script(require 'scripts/GlobalScript.php', 'global-scripts');
```

!!! tip "Cache-Busting"
    Behind the scenes, PhpSPA creates unique, versioned links like `/phpspa/assets/global-styles-a1b2c3d4.css`, which allows the browser to cache them effectively. The optional second parameter (`'global-styles'`) is a name added to the file for easier debugging.

## Component-Specific Assets

You also have two ways to add assets that only belong to a specific component.

=== "Method Chaining (Cached)"

    This works just like global assets. The code is served in its own cached file.

    ```php
    <?php
    $profileComponent = new Component(fn() => '<div>...</div>');

    $profileComponent
       ->route('/profile')
       ->styleSheet(fn() => '.profile-card { border: 1px solid #ccc; }');
    ```

=== "Inline Tags (Uncached)"

    For very small, component-specific tweaks, you can write standard `<style>` and `<script>` tags directly inside your component's `heredoc` string.

    ```php
    <?php
    function UserProfile() {
       return <<<HTML
          <style>
             /* This style is not served as a separate cached file */
             .profile-card {
                padding: 20px;
             }
          </style>

          <div class="profile-card">
             <h2>User Profile</h2>
          </div>
       HTML;
    }
    ```

!!! warning "Inline Assets"
    **Important:** This is the simplest method, but these inline assets **will not be externally cached** by the browser. It's best reserved for small, non-critical snippets.

## Randomizing Asset Names

When you attach an asset, the optional second parameter (`global-styles`, `global-scripts`) is **prepended to the generated link** as a readable label — great for debugging, since you can tell at a glance what each file is.

PhpSPA already appends a content hash to every asset URL, so links **automatically change whenever the code is updated** — cache-busting works with or without this method. `randomizeAssetName()` isn't about caching. It simply **removes that prepended name**, leaving only the hash in the link. The goal is **obscurity**: without the label, someone scanning your page source can't tell what each asset file is for at a glance.

**Example (`index.php`):**

```php
<?php
$app = new App($layout);

$app->styleSheet(require 'styles/GlobalStyle.php', 'global-styles');
$app->script(require 'scripts/GlobalScript.php', 'global-scripts');

// Drop the readable name labels from asset links
$app->randomizeAssetName();
```

With the label: `/phpspa/assets/global-styles-a1b2c3d4.css` — the name tells you what it is.
After `randomizeAssetName()`: the label is gone and only the hash remains, so the link no longer reveals the file's purpose.

!!! note "Obscurity, not security"
    This only strips the readable label from asset links; it is **not** a security measure. The files are still publicly served. Keep it for the mild obfuscation, not as protection.

!!! tip "Development vs Production"
    It's recommended to **remove** this call in development, keeping the readable name labels so generated asset files are easier to identify while debugging.

!!! info "Since v2.0.7"
    `randomizeAssetName()` is chainable and returns the `App` instance, so it fits anywhere in your configuration chain.

## Combining Assets Per Component

By default, PhpSPA generates **one link per asset** — if a component attaches 6 stylesheets and 4 scripts, that's 10 separate asset URLs, and each one is compressed in its own request (its own esbuild/native-compressor invocation).

`combineAssetsPerComponent()` changes that. When enabled, PhpSPA **bundles all callable assets of the same kind into a single link**, so instead of N requests you get at most:

- **1 combined CSS link** per component (all its stylesheet callables)
- **1 combined JS link** per component (all its script callables)
- **1 combined CSS link** for all global stylesheets
- **1 combined JS link** for all global scripts

**Example (`index.php`):**

```php
<?php
$app = new App($layout);

// Enable asset combining (recommended for production)
$app->combineAssetsPerComponent();
```

For a component with several styles and scripts, this collapses many compression passes into just two:

```php
<?php
$dashboard = new Component(fn() => '<div>...</div>');

$dashboard
   ->route('/dashboard')
   ->styleSheet(require 'styles/layout.php')
   ->styleSheet(require 'styles/cards.php')
   ->styleSheet(require 'styles/sidebar.php')
   ->script(require 'scripts/charts.php')
   ->script(require 'scripts/realtime.php');

// With combineAssetsPerComponent():
//   3 stylesheets  -> 1 combined CSS link
//   2 scripts      -> 1 combined JS link
// Instead of 5 separate links and 5 compression passes.
```

### Why it's faster

The expensive part of asset compression is the **per-request process spawn** (launching esbuild, the native-compressor FFI call), not the size of the code. Paying that cost once for a 10k-line bundle is dramatically cheaper than paying it ten times for ten 1k-line files:

| Approach | Spawn cost | Result |
|---|---|---|
| 10 separate files | 10 × spawn overhead | Slowest — spawn tax paid 10× |
| 1 combined bundle | 1 × spawn overhead | Fastest — spawn tax paid once |

Minification time grows **sublinearly** with code size (the compressor works on an AST, not byte-by-byte), so combining 10 × 1k lines into one 10k-line pass is typically several times faster overall.

### Grouping rules

Assets are combined **by type**, so they can be merged into a single tag safely:

- Scripts are grouped by their `type` attribute. If you call `->script(fn() => ..., type: 'module')` and `->script(fn() => ...)` (default `text/javascript`), those are **two different groups** and produce two links.
- Stylesheets are grouped by their `rel`. Standard `rel="stylesheet"` links combine together.
- **External links** (a `script()`/`link()` given a URL string instead of a callable) are **never combined** — they stay as individual tags pointing at their original source.

!!! tip "Use default types for maximum combining"
    If all your `->script(fn() => ...)` calls omit the `type` argument and all your `->link(fn() => ...)` calls omit `rel`, every callable asset in a component falls into one group — giving you exactly **one CSS link and one JS link per component**.

!!! warning "Production only"
    This is recommended for **production**. The combined link's cache key uses the **latest modification time across all files in the bundle**, so editing *any* file in a group invalidates the whole bundle's cached output. That's correct behavior, but it means coarser cache invalidation during active development — keep individual links in dev for easier debugging.

!!! note "Auto-enables randomizeAssetName"
    A combined bundle has no single readable name, so `combineAssetsPerComponent()` automatically enables [`randomizeAssetName()`](#randomizing-asset-names) — combined links are hash-only.

!!! info "Since v2.0.15"
    `combineAssetsPerComponent()` is chainable and returns the `App` instance. Component JavaScript bundles are still wrapped in an IIFE so component scope stays isolated across SPA navigation.
