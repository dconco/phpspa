<?php

namespace PhpSPA\Interfaces;

/**
 * Component interface for PhpSPA framework
 *
 * This interface defines the contract for all components within the PhpSPA
 * framework, including methods for component configuration, routing, and
 * rendering behavior. It ensures consistent component structure and behavior.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
interface  IComponent
{
    /**
     * Constructor for the Component class.
     *
     * Initializes the component with a callable that defines the component function.
     *
     * @param callable $component The callable representing the component logic.
     * @see https://phpspa.tech/core-concepts
     */
    public function __construct(callable $component);

    /**
     * Sets the title for the component.
     *
     * @param string $title The title to set.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/component-configuration
     */
    public function title(string $title): self;

    /**
     * Sets a meta tag for the component's initial HTML response.
     *
     * @param string|null $name Standard meta "name" attribute (e.g., description, keywords).
     * @param string|null $content Content associated with the meta tag.
     * @param string|null $property Open Graph "property" attribute value.
     * @param string|null $httpEquiv HTTP-EQUIV attribute value.
     * @param string|null $charset Charset declaration (for `<meta charset="...">`).
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self Returns the current instance for method chaining.
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
     * Sets the method name for the component.
     *
     * @param string ...$method The name of the method to set, default to "GET|VIEW".
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/component-configuration/#specifying-http-methods
     */
    public function method(string ...$method): self;

    /**
     * Sets the current route for the component.
     *
     * @param string|array ...$route The route to be set.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/advanced-routing
     */
    public function route(string|array ...$route): self;

    /**
     * Shows that the given route value is a pattern in `fnmatch` format
     * @since v2.0.4
     */
    public function pattern(): self;

    /**
     * Make the component show only for that specific route
     * @since v2.0.4
     */
    public function exact(): self;

    /**
     * This loads the component with the specific ID as a layout on the exact URL on this page
     * 
     * @param string ...$componentName The component with the registered names
     * @since v2.0.4
     */
    public function preload(string ...$componentName): self;

    /**
     * This is a unique key for each components to use for preloading
     * 
     * @param string $value The unique name to give this component
     * @since v2.0.4
     */
    public function name(string $value): self;

    /**
     * Sets the target ID for the component.
     *
     * @param string $targetID The identifier of the target element.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/component-configuration/#setting-the-target-render-element
     */
    public function targetID(string $targetID): self;

    /**
     * Enables case sensitivity for the component.
     *
     * Sets the internal flag to treat operations as case sensitive.
     *
     * @return self Returns the current instance for method chaining.
     * @https://phpspa.tech/routing/component-configuration/#route-case-sensitivity
     */
    public function caseSensitive(): self;

    /**
     * Sets the component to operate in a case-insensitive mode.
     *
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/component-configuration/#__tabbed_1_2
     */
    public function caseInsensitive(): self;

    /**
     * Register a component middleware/guard (work-in-progress).
     *
     * Middleware behaves like a normal component function, and receives two parameters:
     *
     * - $req: the current request
     * - $next: the next middleware/handler to call
     *
     * Signature: `callable(Request $req, Closure $next): mixed`
     * 
     * Example:
     * ```php
     * <?php
     * new Component(...)->middleware(function (Request $req, Closure $next) {
     *    if ($req->auth()->bearer) {
     *        return $next();
     *    }
     * 
     *    http_response_code(403);
     *    return "<h2>Unauthorized</h2>";
     * });
     * ```
     * 
     * @since v2.0.5
     * @param callable $middleware
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/references/middleware/#component-middleware
     */
    public function middleware(callable $middleware): self;

    /**
     * Sets the script to be executed when the component is mounted.
     *
     * @param callable|string $content The script to be executed.
     * @param string|null $name Optional name for the script asset.
     * @param string|null $type The type of script the content should be treated as
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#component-specific-assets
    */
    public function script(callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []): self;

    /**
     * Sets the stylesheet to be executed when the component is mounted.
     *
     * @deprecated Use `Component::link()` instead
     * @param callable|string $content The stylesheet to be executed.
     * @param string|null $name Optional name for the stylesheet.
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#component-specific-assets
     */
    public function styleSheet(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;

    /**
     * Sets the link tag to be executed when the component is mounted.
     *
     * @param callable|string $content The stylesheet to be executed.
     * @param string|null $name Optional name for the stylesheet.
     * @param string|null $type The type of style sheet the content should be treated as
     * @param string|null $rel The relationship attribute applied to the generated <link> tag (e.g., "stylesheet", "preload")
     * @param array $attributes Optional additional attributes as key => value pairs.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#component-specific-assets
     * @since v2.0.5
     */
    public function link(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): self;

    /**
     * This sets the component to be called every particular interval given
     *
     * @param int $milliseconds
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/requests/auto-reloading-components
     */
    public function reload(int $milliseconds): self;

    /**
    * Renders a component by executing it and formatting the output.
    *
    * @param callable $component The component to render.
    * @return string The rendered output.
    */
    public static function Render(callable $component): string;
}
