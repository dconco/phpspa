<?php

namespace PhpSPA\Interfaces;

/**
 * Component interface for PhpSPA framework
 *
 * This interface defines the contract for all components within the PhpSPA
 * framework, including methods for component configuration, routing, and
 * rendering behavior. It ensures consistent component structure and behavior.
 *
 * @package PhpSPA\Interfaces
 * @author dconco <me@dconco.tech>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
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
     * Sets the method name for the component.
     *
     * @param string $method The name of the method to set, default to "GET|POST".
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/component-configuration/#specifying-http-methods
     */
    public function method(string $method): self;

    /**
     * Sets the current route for the component.
     *
     * @param array|string $route The route to be set.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/routing/advanced-routing
     */
    public function route(array|string $route): self;

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
     * Sets the script to be executed when the component is mounted.
     *
     * @param callable $script The script to be executed.
     * @param string|null $name Optional name for the script asset.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#component-specific-assets
    */
    public function script(callable $script, ?string $name = null): self;

    /**
     * Sets the stylesheet to be executed when the component is mounted.
     *
     * @param callable $style The stylesheet to be executed.
     * @param string|null $name Optional name for the stylesheet.
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/performance/managing-styles-and-scripts/#component-specific-assets
     */
    public function styleSheet(callable $style, ?string $name = null): self;

    /**
     * This sets the component to be called every particular interval given
     *
     * @param int $milliseconds
     * @return self Returns the current instance for method chaining.
     * @see https://phpspa.tech/requests/auto-reloading-components
     */
    public function reload(int $milliseconds): self;
}
