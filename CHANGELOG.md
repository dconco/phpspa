# CHANGELOG

## v2.0.1 (Current)

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

**Documentation:** [hooks/use-fetch](https://phpspa.tech/references/helpers/fmt)

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

## v1.1.1

✅ Fixes Bugs and Errors.

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
