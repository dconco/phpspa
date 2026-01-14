# CHANGELOG

## v2.0.5.81 (Stable) (PHP 8.1)

This is the **PHP 8.1 compatibility** release line.
It keeps the same public API while removing syntax that is not supported on PHP 8.1.

**Installation:**
```bash
composer require dconco/phpspa:v2.0.5.81
```

### What's Changed

- **PHP 8.1 compatibility:** Removed PHP 8.3 typed class constants (e.g. `public const string X = ...`) and adjusted `string|false` type hints to avoid parse errors on PHP 8.1.
- **Includes PHP 8.3 line fixes:** Also contains the PHP 8.4 property-hook removal from the `v2.0.5.83` compatibility line.

### Notes

- If your app is pinned to PHP `^8.1`, use this tag/release line.
- The mainline releases may continue using newer PHP features where appropriate.




## v2.0.5.82 (Stable) (PHP 8.2)

This is the **PHP 8.2 compatibility** release line.
It keeps the same public API while removing syntax that is not supported on PHP 8.2.

**Installation:**
```bash
composer require dconco/phpspa:v2.0.5.82
```

### What's Changed

- **PHP 8.2 compatibility:** Removed PHP 8.3 typed class constants (e.g. `public const string X = ...`) to avoid parse errors on PHP 8.2.
- **Includes PHP 8.3 line fixes:** Also contains the PHP 8.4 property-hook removal from the `v2.0.5.83` compatibility line.

### Notes

- If your app is pinned to PHP `^8.2`, use this tag/release line.
- The mainline releases may continue using newer PHP features where appropriate.




## v2.0.5.83 (Stable) (PHP 8.2)

This is the **PHP 8.3 compatibility** release line.
It removes PHP 8.4-only syntax while keeping the same public Component API and runtime behavior.

**Installation:**
```bash
composer require dconco/phpspa:v2.0.5.83
```

### What's Changed

- **PHP 8.3 compatibility:** Replaced PHP 8.4 property hook syntax in the core component implementation with PHP 8.3-compatible backing properties and normalizer methods.
- **No API changes:** Fluent component configuration (`->method()`, `->route()`, etc.) remains unchanged.

### Notes

- If your app is pinned to PHP `^8.3`, use this tag/release line.
- The mainline releases may continue using PHP 8.4+ features where appropriate.





## v2.0.5.2 (Current) (Stable) (PHP 8.4)

## v2.0.5.1

Fixed the router by:

1. **Adding segment count validation:** Routes now check that the request URI has the same number of segments as the route pattern.
So `/chats` (1 segment) won't match `/{username}/profile` (2 segments).

2. **Changed regex pattern from `{.*}` to `([^/]+)`:** This matches only one path segment (any characters except forward slashes), preventing greedy matching across multiple segments.

Now `/{username}/profile` will correctly match `/dconco/profile` but not `/dconco`.





## v2.0.5

**Installation:**
```bash
composer require dconco/phpspa:v2.0.5
```

### What's Added

#### **Middleware (App & Component)**

- Added `->middleware()` API to both `App` and `Component` for guards/logging and request shaping.
- Middleware signature: `callable(PhpSPA\Http\Request $request, Closure $next): mixed`.
- Middleware and the final handler run on the **same `Request` instance** (no request re-passing through `$next`), so you can attach values once and read them later.

**Example:**

```php
$app->middleware(function (Request $request, Closure $next) {
   $request->user = Auth::user();
   return $next();
});

new Component(function (Request $request) {
   $user = $request->user;
   return "<h1>Hello {$user->name}</h1>";
})
   ->route('/')
   ->middleware(function ($req, $next) {
      if (!$req->user) {
         http_response_code(403);
         return '<h2>Unauthorized</h2>';
      }
      return $next();
   });
```

#### **Component-Level SEO Controls** 🧭

- Added chained `->meta()` API to every component so route-specific descriptions, keywords, Open Graph, or HTTP-EQUIV tags can be declared right where the component is defined.
- Runtime now injects those tags only during the initial HTML.
- Layout stays clean; global meta is still available via the layout if needed.
- Added `App::meta()` for layout-wide defaults—global entries merge with (and can be overridden by) per-component metadata so you define canonical tags once.

**Example:**

```php
new Component(...)
   ->route('/')
   ->title('PhpSPA Design System')
   ->meta(name: 'description', content: 'Design-forward PhpSPA starter')
   ->meta(property: 'og:title', content: 'PhpSPA Design System');
```

**Documentation:** [references/component-meta](https://phpspa.tech/references/component-meta)


#### **`useCallback()`**

- Added `useCallback()` to client side.
   ```javascript
      const toggleNav = useCallback((event: MouseEvent) => {
         // Stable reference no matter how often this runs
      }, []);
   ```

#### **Deterministic Global Script Queue**

- `App::script()` now accepts either a callable (inline script) or a direct string path (e.g., `/src/main.ts`).
- Scripts render in the order they are registered, so you can emit inline data (tokens, config, feature flags) and then queue your bundler entry or CDN script immediately after.

**Example:**

```php
$token = Component\useFunction('Toggle')->token;

$app
   ->script(fn () => "<script>window.token = '{$token}';</script>")
   ->script('/src/main.ts');
```

#### **Global Asset API Clean-up**

- Added `App::link()` (and `Component::link()`) for defining `<link>` tags; the older `->styleSheet()` helpers are now deprecated aliases.

- Asset helpers (`script()/link()`) take an `$attributes` array so you can emit `crossorigin`, `referrerpolicy`, `fetchpriority`, etc., without manual string building.

- The layout auto-creates a `<head>` block if it is missing, ensuring all scripts/meta/styles have a deterministic insertion point.

#### **`useModule()`**

- Added `App::useModule()` to specify if this application is used with node, like serving with vite. This is going to stop adding phpspa asset links, and you would have to install the `@dconco/phpspa` npm package, `npm install @dconco/phpspa` to use the `phpspa` client features.


### What's Changed
- Changed `App::static()` to `App::useStatic()`.


---



## v2.0.4

> [!IMPORTANT]
> This version requires **PHP 8.4 or higher**

**Installation:**
```bash
composer require dconco/phpspa:v2.0.4
```

### **Client-Side Hooks** ⚛️

- **`useEffect()`** - Manage side effects and cleanups in your component scripts with dependency tracking

**Example:**

```javascript
// Inside your component's script tag
useEffect(() => {
   const btn = document.getElementById('btn');
   const handleClick = () => console.log('Clicked!');
   
   btn.addEventListener('click', handleClick);

   // Cleanup function runs before next effect or on unmount
   return () => btn.removeEventListener('click', handleClick);
}, ['stateKey']); // Re-runs only when 'stateKey' changes
```

**Documentation:** [references/hooks/clients/use-effect](https://phpspa.tech/references/hooks/clients/use-effect)


### **Core Fixes** 🔧

- **Script Loading Order** - Moved assets scripts to `<head>` to prevent race conditions (Fixes `useEffect` definition timing)

### **Advanced Component Features** 🎯

- **Component Preloading** - Load multiple components on different target IDs simultaneously for multi-section layouts
- **Exact Route Matching** - Render components only on exact URL matches with automatic fallback to default content
- **Pattern-based Routing** - Match routes using fnmatch-style patterns (e.g., `/blog/*`)
- **Named Components** - Assign unique identifiers to components for easy reference
- **Multiple Routes & Methods** - Define multiple routes and HTTP methods in a cleaner way

**Perfect for building:**
- Multi-section layouts (messaging apps, dashboards)
- Dynamic content with independent sections
- Complex routing patterns

**Quick Example:**

```php
use PhpSPA\Component;

// User list sidebar (always visible)
$userList = (new Component(function() {
   return "<aside>User List</aside>";
}))
   ->route('/')
   ->targetID('sidebar')
   ->name('userList');

// Chat view with preloaded sidebar
$chatView = (new Component(function(array $path) {
   return "<main>Chat with user {$path['id']}</main>";
}))
   ->route('/chat/{id: int}')
   ->targetID('content')
   ->preload('userList')  // Sidebar stays visible
   ->exact();             // Reverts when navigating away

// Pattern matching for blog posts
$blog = (new Component(function() {
   return "<article>Blog Content</article>";
}))
   ->route('/blog/*', '/articles/*')
   ->pattern();

// Multiple methods
$form = (new Component(function() {
   return "<form>Contact Form</form>";
}))->method('GET', 'POST');
```

**Documentation:** [references/preloading-component](https://phpspa.tech/references/preloading-component/)


### **DOM Utilities** 🛠️

- **`DOM::Title()`** - Get or set page title dynamically from within components

**Example:**

```php
use PhpSPA\DOM;

function UserProfile() {
   DOM::Title('User Profile - My App');
   
   return "<h1>User Profile</h1>";
}

// Get the current title
$currentTitle = DOM::Title(); // Returns: "User Profile - My App"
```

**Documentation:** [references/dom-utilities](https://phpspa.tech/references/dom-utilities/)




### **Enhanced Routing & Middleware** 🛣️

- **Middleware Support** - Add middleware to routes and groups for authentication, logging, etc.
- **Route Prefixing** - Group routes under common paths using `App::prefix()` or `Router::prefix()`.
- **Static File Serving** - Serve static files easily with `App::static()`.
- **Improved Request/Response** - New methods like `Request::urlParams()` and `Response::sendFile()`.
- **Component Rendering** - Render components manually using `Component::Render()`.

**Example:**

```php
$app->static('/', '../public');

$app->prefix('/api', function (Router $router) {
   // Middleware for this group
   $router->middleware(function (Request $req, Response $res, Closure $next) {
      if ($req->urlParams('id') !== 1) {
         return $res->unauthorized('Unauthorized');
      }
      return $next();
   });

   $router->get('/user/{id: int}', function (Request $req, Response $res) {
      return $res->success("User ID: " . $req->urlParams('id'));
   });

   // Each route can get their own middleware
   $userMiddleware = function (Request $req, Response $res, Closure $next) {
      if ($req->urlParams('id') !== 1) {
         return $res->unauthorized('Unauthorized');
      }
      return $next();
   };

   $router->get('/user/{id: int}', $userMiddleware, function (Request $req, Response $res) {
      return $res->success("User ID: " . $req->urlParams('id'));
   });
});
```

**Documentation:** [references/router](https://phpspa.tech/references/router/)




---



## v2.0.3

**Installation:**
```bash
composer require dconco/phpspa:v2.0.3
```

### ✨ New Features

#### **Native C++ Compression Engine** ⚡
Added lightning-fast native C++ compressor with FFI support for HTML/CSS/JS minification:

- **Zero-overhead compression** - Native C++ performance without PHP overhead
- **FFI Integration** - Seamless PHP-to-C++ bridge using PHP's FFI extension
- **Cross-platform binaries** - Prebuilt libraries for Windows (`.dll`), Linux (`.so`), and macOS (`.dylib`)
- **Auto-detection** - Automatically finds compression library in standard locations
- **Fallback support** - Gracefully falls back to PHP compression if native library unavailable
- **Multiple compression levels** - Disabled, Basic, Aggressive, and Extreme modes
- **Performance monitoring** - Response headers show which engine handled compression (`X-PhpSPA-Compression-Engine`)

**Usage:**
```php
<?php
use PhpSPA\Compression\Compressor;

// Enable native compression
$app->compression(Compressor::LEVEL_AGGRESSIVE, true);

// Force native mode (optional)
putenv('PHPSPA_COMPRESSION_STRATEGY=native');
```

**Documentation:** [references/compression](https://phpspa.tech/references/compression/)




---




## v2.0.1

### ✨ New Features

#### **Async HTTP Requests** 🚀

Added asynchronous HTTP request support to `useFetch()` hook with true parallel execution:

- Non-blocking requests with `async()` method
- Parallel execution with `AsyncResponse::all()` using `curl_multi`
- Generator-based `AsyncPool` with `stream()` method for processing responses as they complete
- Promise-like `then()` callbacks
- Up to 3x faster for concurrent requests

**Documentation:** [hooks/use-fetch](https://phpspa.tech/references/hooks/use-fetch)

#### **Component Props Type Preservation** 🎯

Added `fmt()` helper function to preserve exact data types when passing props between components:

- Supports passing custom classes, interfaces, and complex objects as component props
- Automatic serialization and deserialization with type preservation
- Works with strings, arrays, objects, and custom class instances

```php
class UserData {
   public function __construct(
      public readonly string $name,
      public readonly int $age
   ) {}
}

$user = new UserData('John', 25);
fmt($user);

// Pass to component - receives exact UserData instance
return "<UserCard>{$user}</UserCard>";
```

**Documentation:** [references/helpers/fmt](https://phpspa.tech/references/helpers/fmt)




---




## v2.0.0

> [!IMPORTANT]
> This is a **MAJOR VERSION RELEASE** with significant breaking changes. Please read the migration guide carefully before upgrading.

### 🚨 Breaking Changes

#### 1. **Namespace Restructuring** 🔄

All namespaces have been changed from `phpSPA\` to `PhpSPA\` (capital P and capital S) for PSR-4 compliance and better naming conventions.

**Migration Required:**

```php
// Before (v1.1.9 and earlier)
use phpSPA\App;
use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Http\Response;

// After (v2.0.0)
use PhpSPA\App;
use PhpSPA\Component;
use PhpSPA\Http\Request;
use PhpSPA\Http\Response;
```

**Files Affected:**
- All autoloaded namespaces in `composer.json`
- All class imports throughout your application
- Interface name changed: `phpSpaInterface.php` → `PhpSPAInterface.php`

#### 2. **Hook API Changes** ⚛️

State management function has been renamed for better clarity and consistency with modern frameworks:

**Migration Required:**

```php
// Before (v1.1.9 and earlier)
$counter = createState("count", 0);

// After (v2.0.0)
use function Component\useState;

$counter = useState("count", 0);
```

#### 3. **Global Script and Stylesheet API Enhancement**

Added optional naming parameter for better asset management:

```php
// Before (v1.1.9 and earlier)
$app->script(function() {
   return "console.log('app loaded');";
});

// After (v2.0.0) - with optional name parameter
$app->script(function() {
   return "console.log('app loaded');";
}, 'app-init');
```

---

### ✨ New Features

#### 1. **New React-like Hooks System** ⚛️

Added modern React-inspired hooks for better component development:

##### `useState()` Hook
Renamed from `createState()` for better consistency:

```php
use function Component\useState;

function Counter() {
   $count = useState("counter", 0);
   return <<<HTML
      <h1>Count: {$count}</h1>
      <button onclick="setState('counter', {$count} + 1)">Increment</button>
   HTML;
}
```

**Documentation:** [hooks/use-state](https://phpspa.tech/hooks/use-state)

##### `useEffect()` Hook - NEW! 🎉
Execute side effects when dependencies change:

```php
use function Component\{useState, useEffect};

function UserProfile() {
   $userId = useState("userId", 1);
   $userData = useState("userData", null);
   
   useEffect(function() use ($userId, $userData) {
      // Fetch user data when userId changes
      $data = fetchUserFromAPI($userId());
      $userData->set($data);
   }, [$userId]);
   
   return <<<HTML
      <div>User: {$userData()->name}</div>
   HTML;
}
```

**Documentation:** [hooks/use-effect](https://phpspa.tech/hooks/use-effect)

#### 2. **Enhanced Security Features** 🔒

##### Content Security Policy (CSP) with Nonce Support - NEW!

Added comprehensive CSP support with automatic nonce generation:

```php
use PhpSPA\Http\Security\Nonce;

// Enable CSP with nonce
Nonce::enable([
   'script-src' => ["https://cdn.jsdelivr.net"],
   'style-src'  => ["https://fonts.googleapis.com"],
   'font-src'   => ["https://fonts.gstatic.com"]
]);

// Use nonce in templates
$nonce = Nonce::nonce();
echo "<script nonce='{$nonce}'>console.log('secure');</script>";
```

**Features:**
- Automatic nonce generation per request
- Configurable CSP directives
- Integration with inline scripts and styles
- Protection against XSS attacks

**Documentation:** [security/content-security-policy](https://phpspa.tech/security/content-security-policy)

#### 3. **Global Helper Functions** 🛠️

Added convenient global helper functions for common operations:

##### `response()` Helper
```php
// Quick response helper
return response(['message' => 'Success'], 200);
return response()->json(['data' => $data]);
return response()->error('Not found', 404);
```

##### `router()` Helper
```php
// Quick router access
router()->get('/api/users', function() {
   return response()->json(['users' => getUsers()]);
});
```

##### `scope()` Helper
```php
// Register component scope variables
scope([
   'User' => fn() => getCurrentUser(),
   'Config' => fn() => getAppConfig()
]);

// Use in components with @ or $ syntax
return "<@User />";
```

##### `autoDetectBasePath()` Helper
```php
// Automatically detect application base path
$basePath = autoDetectBasePath();
```

##### `relativePath()` Helper
```php
// Get relative path from current URI
$path = relativePath(); // e.g., '../../'
```

#### 4. **Component Scope System** 🎯

New component variable registration system for shared data:

```php

function Header() {
   $CurrentUser = function() {
     return $_SESSION['user'] ?? null;
   };
   
   $AppName = fn() => 'My App';

   // Export component variables
   scope(compact('CurrentUser', 'AppName'));

   // Then you can use it inside your template
   return <<<HTML
      <h1><@AppName /></h1>
      <span>Welcome, <@CurrentUser /></span>
   HTML;
}
```

#### 5. **Path Resolution System** 🗺️

Enhanced path handling with automatic base path detection:

```php
use PhpSPA\Core\Helper\PathResolver;

// Auto-detect base path
$base = PathResolver::autoDetectBasePath();

// Get relative path
$relative = PathResolver::relativePath();

// Resolve asset paths
$assetPath = PathResolver::resolve('/assets/style.css');
```

### 📦 Project Metadata Updates

#### composer.json Updates:

**New Contributor Added:**
- Samuel Paschalson (@SamuelPaschalson) - Contributor for Router & Response features

**Namespace Changes:**
- `phpSPA\` → `PhpSPA\` (all namespaces)

**Autoload Updates:**
- Added `src/global/autoload.php` for global helpers
- Updated component file references

---

### 🙏 Credits

- **Maintainer:** [Dave Conco](https://github.com/dconco)
- **Contributor:** [Samuel Paschalson](https://github.com/SamuelPaschalson) - Router & Response overhaul
- **Community:** All contributors and testers who helped make v2.0.0 possible




---




## v1.1.8

### [Added]

1. **Router & Response overhaul** ✅

   - Automatic routing dispatch via `register_shutdown_function` with `Router::handle()` available for manual dispatch.
   - Route registration now integrates with `phpSPA\Core\Router\MapRoute` for robust pattern and typed-parameter matching.
   - Response helpers (`Response::json`, `Response::error`, etc.) for concise route callbacks.

   Docs: https://phpspa.tech/v1.1.8



---




## v1.1.7

### [Added]

1. **Global Asset Management System** ✅

   Added comprehensive asset management capabilities to the App class for better control over global scripts, stylesheets, and caching:

   **Features:**
   - **Asset Cache Control**: `assetCacheHours()` method to configure asset caching duration
   - **Global Scripts**: `script()` method to add application-wide JavaScript that executes on every component render
   - **Global Stylesheets**: `styleSheet()` method to add application-wide CSS that applies to every component render
   - **Session-based Asset Links**: Enhanced asset delivery system using session-based links for improved performance

   **Usage:**

   ```php
   use phpSPA\App;

   $app = new App('layout')
       ->assetCacheHours(48)  // Cache assets for 48 hours
       ->script(function() {
           return "console.log('Global script loaded');";
       })
       ->styleSheet(function() {
           return "body { font-family: 'Arial', sans-serif; }";
       });
   ```

   **Methods Added:**
   - `App::assetCacheHours(int $hours)` - Configure asset caching duration (0 for session-only, default is 24 hours)
   - `App::script(callable $script)` - Add global scripts that execute on every component render
   - `App::styleSheet(callable $style)` - Add global stylesheets that apply to every component render

   **Files Modified:**
   - `app/client/App.php` - Added the three new public methods with full documentation
   - `app/core/Helper/AssetLinkManager.php` - Enhanced with cache configuration management
   - `app/core/Impl/RealImpl/AppImpl.php` - Updated asset generation logic to support global assets




---




## v1.1.6

### [Fixed]

1. **HTML Compression Bug Fixes**: Fixed critical issue where spaces between HTML element names and attributes were being incorrectly removed during compression, which could break HTML structure.

2. **JavaScript Compression Improvements**: Enhanced JavaScript minification with better handling of:
   - Method calls and constructor patterns
   - String literals and complex JavaScript structures  
   - IntersectionObserver and other modern JavaScript APIs in aggressive compression mode
   - UTF-8 encoding and compression of special characters and emojis

3. **Test Suite Enhancements**: Fixed function redeclare errors in test files and improved test reliability:
   - Removed duplicate `compressJs()` function in `ComprehensiveJsCompressionTest.php`
   - Enhanced UTF-8 integration tests for better validation
   - Fixed callback logic in `basicMinify` for proper script/style tag detection

### [Changed]

1. **Script/Style Tag Detection**: Changed from `isset()` checks to `!empty()` checks for more reliable script and style tag detection in HTML compression.

2. **Template Improvements**: Updated HomePage component to remove dynamic icon source and enhanced scrolling functionality to scroll to top when no hash is present or target element is not found.

3. **Test Pattern Handling**: Updated JavaScript compression tests to ensure proper handling of various patterns and improved consistency across test files.

### [Added]

1. **Enhanced UTF-8 Support**: Improved handling and testing of UTF-8 characters, special characters, and emojis in compression routines.

2. **Better Error Handling**: Added more robust error handling in compression callback logic to prevent incorrect processing of whitespace as script tags.




---




## v1.1.5

> [!IMPORTANT]
> This PHPSPA version requires the [`dconco/phpspa-js`](https://github.com/dconco/phpspa-js) version above `v1.1.7` to be able to work

### [Added]

1. **HTML Compression & Minification System** ✅

   Added comprehensive HTML/JS/CSS compression and minification system with automatic semicolon insertion (ASI) for safe JavaScript minification:

   **Features:**
   - **Multi-level compression**: None, Basic, Aggressive, Extreme, Auto
   - **Gzip compression**: Automatic when supported by client  
   - **Environment auto-detection**: Development, Staging, Production presets
   - **Smart JS minification**: Preserves functionality with automatic semicolon insertion at risky boundaries
   - **CSS minification**: Removes comments, whitespace, and optimizes selectors
   - **Performance optimized**: 15-84% size reduction possible

   **Usage:**

   ```php
   use phpSPA\Compression\Compressor;

   // Auto-configuration (recommended)
   $app = new App('layout')->compression(Compressor::LEVEL_AUTO, true);

   // Manual control
   $app = new App('layout')->compression(Compressor::LEVEL_EXTREME, true);

   // Environment-specific
   $app = new App('layout')->compressionEnvironment(Compressor::ENV_PRODUCTION);
   ```

   **Files Added:**
   - `app/core/Utils/HtmlCompressor.php` - Main compression engine with JS/CSS minification
   - `app/core/Config/CompressionConfig.php` - Configuration management
   - `tests/Test.php` - Unified test runner (CLI-only)
   - `tests/HtmlCompressionTest.php` - HTML compression tests
   - `tests/JsCompressionTest.php` - JavaScript ASI/semicolon insertion tests
   - `.github/workflows/php-tests.yml` - CI/CD testing workflow

2. Added `__call()` alias of `phpspa.__call()` but changed the logic on how it works:

   - You'll import the new created function `useFunction()` and provide the function you're to use as parameter, in your component:

      ```php
      <?php
      // your Login component, make sure it's global function (or namespaced)
      function Login($args) { return "<h2>Login Component</h2>"; }

      // in your main component

      // make sure you include the use function namespace
      use function Component\useFunction;

      $loginApi = useFunction('Login'); // Login since it's not in a namespace, if it is then include them together, eg '\Namespace\Login'

      return <<<HTML
         <script data-type="phpspa/script">
            htmlElement.onclick = () => {
               __call("{$loginApi->token}", "Arguments")
            }
         </script>
      HTML;
      ```

3. Provided direct PHP integration for calling PHP function from JS.

   - If you want a faster method, than calling manual with JS, use this:

      ```php
       // in your component, related to the earlier example.
       $loginApi = useFunction('Login'); // the function to call

       return <<<HTML
         <script data-type="phpspa/script">
            htmlElement.onclick = () => $loginApi; // this generates the JavaScript code

            // to get the result (running with async)
            htmlElement.onclick = async () => {
               const response = await {$loginApi('arguments')}; // if there's argument, it'll like this
               console.log(response) // outputs the response from the Login function
            }
         </script>
       HTML;
      ```

4. Support for class components (e.g., `<MyClass />`)

5. Namespace support for class components (e.g., `<Namespace.Class />`)

6. Classes require `__render` method for component rendering

7. **Method Chaining Support to App Class**

   You can now fluently chain multiple method calls on an App instance for cleaner and more expressive code.

   **New Usage Example:**

   ```php
   $app = (new App(require 'Layout.php'))
      ->attach(require 'components/Login.php')
      ->defaultTargetID('app')
      ->defaultToCaseSensitive()
      ->cors()
      ->run();
   ```

8. New `<Component.Csrf />` component for CSRF protection

- Support for multiple named tokens with automatic cleanup

- Built-in token expiration (1 hour default)

- Automatic token generation and validation

   **Features:**

- Automatic token rotation

- Prevents token reuse (optional via `$expireAfterUse`)

- Limits stored tokens (10 max by default)

- Timing-safe validation

   **Security:**

- Uses cryptographically secure `random_bytes()`

- Implements `hash_equals()` to prevent timing attacks

- Tokens automatically expire after 1 hour

   **Example Workflow**

- **In Form:**

   ```php
   <form>
      <Component.Csrf name="user-form" />
      <!-- other fields -->
   </form>
   ```

- **On Submission:**

   ```php
   use Component\Csrf;

   $csrf = new Csrf("user-form"); // the csrf form name

   if (!$csrf->verify())) {
      die('Invalid CSRF token!');
   }

   // Process form...
   ```

> [!NOTE]
> By default the CSRF token cannot to be used again after successful validation until the page is refreshed to get new token.
> To prevent this, pass false to the function parameter: `$csrf->verify(false)`

### [Fixed]

1. **Component rendering with nested children**: Fixed issue where nested components were not properly processing their children before being passed to parent components.

### [Changed]

1. Changed from reference-based processing to return-value based processing for cleaner data flow and more reliable component resolution on `ComponentFormatter`

2. JS now check and execute all scripts & styles from all component no matter the type (we are no more using data-type attributes)

3. `\phpSPA\Component` namespaces are now converted to `\Component` namespace.

4. Changed how JS -> PHP connection core logic works

5. Made `__call()` function directly from Js x10 more secured

6. Edited `StrictTypes` class and make the `string` class worked instead of `alnum` and `alpha`

7. Made CORS configuration optional with default settings

8. CORS method now loads default config when called (previously no defaults available)

### [Removed]

1. Removed `__CONTENT__` placehover. It now renders directly using the target ID

2. Removed deprecated `<Link />` Alias, use `<Component.Link />` instead.

## v1.1.4

- Updated phpSPA core from frontend to use the `Request` class instead of just global request `$_REQUEST`

- Added Hooks Event Documentation. [View Docs](https://phpspa.tech/hooks-event/)

## v1.1.3

- Added new `Session` utility class in `phpSPA\Http` namespace for comprehensive session management

- `Session::isActive()` - Check if session is currently active

- ✨ `Session::start()` - Start session with proper error handling

- ✨ `Session::destroy()` - Destroy session with complete cleanup including cookies

- ✨ `Session::get()` - Retrieve session variables with default value support

- ✨ `Session::set()` - Set session variables

- ✨ `Session::remove()` - Remove single or multiple session variables (supports array input)

- ✨ `Session::has()` - Check if session variable exists

- ✨ `Session::regenerateId()` - Regenerate session ID for security

## v1.1.2

- ✨ Made `route()` method optional in component definition

- ✨ Added `reload(int $milliseconds = 0)` method for auto-refreshing components

- ✨ Added `phpspa.__call()` JavaScript function for direct PHP function calls

- ✨ Added `cors()` method to App class for CORS configuration

[View Latest Documentation](https://phpspa.tech/v1.1.2)



---



## v1.1.1

✅ Fixes Bugs and Errors.



---




## v1.1.0

- ✨ Added file import `phpSPA\Component\import()` function for importing files (images) to html. @see [File Import Utility](https://phpspa.tech/v1.1/1-file-import-utility)

- ✨ Added `map()` method to state management, can now map array to html elements, `$stateItems->map(fn (item) => "<li>{$item}</li>")`. @see [Mapping In State Management](https://phpspa.tech/v1.1/2-mapping-in-state-management)

- ✨ Added component to be accessible by html tags, `<Component />`, both inline tags and block tags `<Component></Component`. @see [Using Component Functions By HTML Tags](https://phpspa.tech/v1.1/3-using-component-functions-by-html-tags)

- ✨ Created component function `<Link />`, and made it be under the `phpSPA\Component` namespace. @see [Link Component](https://phpspa.tech/v1.1/4-link-component)

- ✨ Added `phpSPA\Component\HTMLAttrInArrayToString()` function, use it when converting `...$props` rest properties in a component as rest of HTML attributes. @see [HTML Attribute In Array To String Conversion](https://phpspa.tech/v1.1/5-html-attr-in-array-to-string-function)

- ✨ Added function `phpSPA\Http\Redirect()` for redirecting to another URL. @see [Redirect Function](https://phpspa.tech/v1.1/6-redirect-function.md)

- ✨ Created component function `<PhpSPA.Component.Navigate />`, for handling browser's navigation through PHP. @see [Navigate Component](https://phpspa.tech/v1.1/7-navigate-component.md)

- ✨ Made JS `phpspa.setState()` available as just `setState()` function.

### Deprecated

- ✨ Using HTML `<Link />` tag without the function namespace is deprecated. You must use the namespace in other to use the component function, `<PhpSPA.Component.Link />` See: [Deprecated HTML Link](https://phpspa.tech/v1.1/4-link-component/#deprecated)




---




## v1.0.0 - Initial Release

### 🧠 New in v1.0.0

- 🌟 **State Management**:

  - ✨ Define state in PHP with `createState('key', default)`.
  - ✨ Trigger re-renders from the frontend via `phpspa.setState('key', value)`.
  - ✨ Automatically updates server-rendered output in the target container.

- 🧩 **Scoped Component Styles & Scripts**:

  - ✨ Use `<style data-type="phpspa/css">...</style>` and `<script data-type="phpspa/script">...</script>` inside your components.
  - ✨ Automatically injected and removed during navigation.

- ⚙️ **Improved JS Lifecycle Events**:

  - ✨ `phpspa.on("beforeload", callback)`
  - ✨ `phpspa.on("load", callback)`

---

## 📦 Installation

```bash
composer require dconco/phpspa
```

Include the JS engine:

```html
<script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
```

---

## 🧱 Coming Soon

- 🛡️ CSRF protection helpers and automatic verification
- 🧪 Testing utilities for components
- 🌐 Built-in i18n tools

---

## 📘 Docs & Links

- GitHub: [dconco/phpspa](https://github.com/dconco/phpspa)
- JS Engine: [dconco/phpspa-js](https://github.com/dconco/phpspa-js)
- Website: [https://phpspa.tech](https://phpspa.tech)
- License: MIT

---

💬 Feedback and contributions are welcome!

— Maintained by [Dave Conco](https://github.com/dconco)
