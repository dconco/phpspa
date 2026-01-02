<?php

namespace PhpSPA\Interfaces;

use PhpSPA\Component;

/**
 * Core PhpSPA application contract
 * 
 * This application provides the foundational implementation for the PhpSPA application framework.
 * It handles layout management, component registration,
 * routing, and rendering logic that powers the single-page application experience.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @see https://phpspa.tech/core-concepts
 */
interface ApplicationContract {
    /**
     * Sets the target ID for the application.
     *
     * @param string $targetID The target ID to be set.
     * @return self
     * @see https://phpspa.tech/layout/#setting-the-default-target-id
     */
    public function defaultTargetID (string $targetID): self;


    /**
     * Sets the default behavior to case sensitive.
     *
     * Implementing this method should configure the system or component
     * to treat relevant operations (such as string comparisons or lookups)
     * as case sensitive by default.
     *
     * @return self
     * @see https://phpspa.tech/routing/component-configuration/#global-case-sensitivity
     */
    public function defaultToCaseSensitive (): self;


    /**
     * Configure HTML compression manually
     *
     * @param int $level Compression level (0=none, 1=auto, 2=basic, 3=aggressive, 4=extreme)
     * @param bool $gzip Enable gzip compression
     * @return self
     * @see https://phpspa.tech/performance/html-compression
     */
    public function compression (int $level, bool $gzip = true): self;


    /**
     * Set cache duration for CSS/JS assets
     *
     * @param int $hours Number of hours to cache assets (0 for session-only) Default is 24 hours
     * @return self
     * @see https://phpspa.tech/performance/assets-caching
     */
    public function assetCacheHours (int $hours): self;


    /**
     * Set compression based on environment
     *
     * @param string $environment Environment: 'development', 'staging', 'production'
     * @return self
     * @see https://phpspa.tech/performance/html-compression/#environment-based-configuration-recommended
     */
    public function compressionEnvironment (string $environment): self;


    /**
     * Add a global script to the application
     *
     * This script will be executed on every component render throughout the application.
     * Scripts are added to the global scripts array and will be rendered alongside
     * component-specific scripts.
     *
     * @param callable|string $content The callable that returns the JavaScript code
     * @param string|null $name Optional name for the script asset
     * @param string|null $type The type of script the content should be treated as
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     */
    public function script (callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []): self;


    /**
     * Add a global stylesheet to the application
     *
     * This stylesheet will be included on every component render throughout the application.
     * Stylesheets are added to the global stylesheets array and will be rendered alongside
     * component-specific styles.
     *
     * @deprecated Use `App::link()` instead
     * @param callable|string $content The callable that returns the CSS code
     * @param string|null $name Optional name for the stylesheet asset
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     */
    public function styleSheet (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;


    /**
     * Add a global link tag to the application
     *
     * This stylesheet will be included on every component render throughout the application.
     * Stylesheets are added to the global stylesheets array and will be rendered alongside
     * component-specific styles.
     *
     * @param callable|string $content The callable that returns the CSS code
     * @param string|null $name Optional name for the stylesheet asset
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @see https://phpspa.tech/performance/managing-styles-and-scripts
     * @since v2.0.5
     */
    public function link (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;

    /**
     * Register global meta tags that render with every initial HTML response.
     *
     * Entries follow the same API as component-level metadata and merge with
     * per-component tags (component tags win when duplicates exist).
     *
     * @param string|null $name Standard meta "name" attribute.
     * @param string|null $content Content associated with the meta tag.
     * @param string|null $property Open Graph "property" attribute value.
     * @param string|null $httpEquiv HTTP-EQUIV attribute value.
     * @param string|null $charset Charset declaration (mutually exclusive with content).
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self
     * @since v2.0.5
     * @see https://phpspa.tech/references/component-meta
     */
    public function meta(
        ?string $name = null,
        ?string $content = null,
        ?string $property = null,
        ?string $httpEquiv = null,
        ?string $charset = null,
        array $attributes = []
    ): self;


    /**
     * Registers a static file path to a route.
     *
     * @param string $route The route to map.
     * @param string $staticPath The static file path.
     * @return ApplicationContract
     * @see https://phpspa.tech/references/router/#static-files
     * @since v2.0.5
     */
    public function useStatic(string $route, string $staticPath): self;

    /**
     * Register a global middleware that applies to all components.
     *
     * Middleware functions receive two parameters:
     *
     * - $req: The current Request instance.
     * - $next: A callable that represents the next middleware or the component itself.
     *          When called, it returns the HTML string output of the component/next middleware.
     *
     * Signature: `callable(Request $req, Closure $next): mixed`
     *
     * Example:
     * 
     * ```php
     * <?php
     * new App()->middleware(function (Request $req, Closure $next) {
     *    if ($req->auth()->bearer) {
     *        return $next();
     *    }
     * 
     *    http_response_code(403);
     *    return "<h2>Unauthorized</h2>";
     * });
     * ```
     * 
     * @param callable $middleware The middleware function.
     * @return self
     * @since v2.0.5
     * @see https://phpspa.tech/references/middleware/#app-middleware
     */
    public function middleware(callable $component): self;

    /**
     * Group routes under a common prefix.
     *
     * @param string $path The prefix path.
     * @param callable $handler The handler function with Router as the parameter.
     * @return self
     * @since v2.0.4
     * @see https://phpspa.tech/references/router/#app-level-prefixing
    */
    public function prefix(string $path, callable $handler): self;

    /**
     * If you are using script module with `@dconco/phpspa` js package, enable this function
     * 
     * @since v2.0.5
     */
    public function useModule(): self;

    /**
     * Configure CORS (Cross-Origin Resource Sharing) settings for the application.
     *
     * Loads default CORS configuration from the config file and optionally merges
     * custom settings provided via the data parameter. Automatically removes
     * duplicate values from array-type configuration options.
     *
     * @param array{
     *   allow_origin: array<string>,
     *   allow_methods: array<string>,
     *   allow_headers: array<string>,
     *   allow_credentials: bool,
     *   supports_credentials: bool,
     *   expose_headers: array<string>,
     *   max_age: int,
     * } $data
     *
     * @return self Returns the current instance for method chaining
     * @see https://phpspa.tech/security/cors
     */
    public function cors (array $data = []): self;


    /**
     * Attaches a component to the current object.
     *
     * @param IComponent|Component $component The component instance to attach.
     * @return self
     */
    public function attach (IComponent|Component $component): self;


    /**
     * Detaches the specified component from the current context.
     *
     * @param IComponent|Component $component The component instance to be detached.
     * @return self
     */
    public function detach (IComponent|Component $component): self;


    /**
     * Runs the application.
     *
     * This method is responsible for executing the main logic of the application,
     * including routing, rendering components, and managing the application lifecycle.
     *
     * @param bool $return If true, returns the rendered output as a string instead of printing it.
     */
    public function run (bool $return = false);
}
