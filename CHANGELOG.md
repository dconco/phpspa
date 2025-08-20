# CHANGELOG

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

- Added Hooks Event Documentation. [View Docs](https://phpspa.readthedocs.io/en/latest/hooks-event/)

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

[View Latest Documentation](https://phpspa.readthedocs.io/en/latest/v1.1.2)

## v1.1.1

✅ Fixes Bugs and Errors.

## v1.1.0

- ✨ Added file import `phpSPA\Component\import()` function for importing files (images) to html. @see [File Import Utility](https://phpspa.readthedocs.io/en/latest/v1.1/1-file-import-utility)

- ✨ Added `map()` method to state management, can now map array to html elements, `$stateItems->map(fn (item) => "<li>{$item}</li>")`. @see [Mapping In State Management](https://phpspa.readthedocs.io/en/latest/v1.1/2-mapping-in-state-management)

- ✨ Added component to be accessible by html tags, `<Component />`, both inline tags and block tags `<Component></Component`. @see [Using Component Functions By HTML Tags](https://phpspa.readthedocs.io/en/latest/v1.1/3-using-component-functions-by-html-tags)

- ✨ Created component function `<Link />`, and made it be under the `phpSPA\Component` namespace. @see [Link Component](https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component)

- ✨ Added `phpSPA\Component\HTMLAttrInArrayToString()` function, use it when converting `...$props` rest properties in a component as rest of HTML attributes. @see [HTML Attribute In Array To String Conversion](https://phpspa.readthedocs.io/en/latest/v1.1/5-html-attr-in-array-to-string-function)

- ✨ Added function `phpSPA\Http\Redirect()` for redirecting to another URL. @see [Redirect Function](https://phpspa.readthedocs.io/en/latest/v1.1/6-redirect-function.md)

- ✨ Created component function `<PhpSPA.Component.Navigate />`, for handling browser's navigation through PHP. @see [Navigate Component](https://phpspa.readthedocs.io/en/latest/v1.1/7-navigate-component.md)

- ✨ Made JS `phpspa.setState()` available as just `setState()` function.

### Deprecated

- ✨ Using HTML `<Link />` tag without the function namespace is deprecated. You must use the namespace in other to use the component function, `<PhpSPA.Component.Link />` See: [Deprecated HTML Link](https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component/#deprecated)

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
- Website: [https://phpspa.readthedocs.io](https://phpspa.readthedocs.io)
- License: MIT

---

💬 Feedback and contributions are welcome!

— Maintained by [Dave Conco](https://github.com/dconco)
