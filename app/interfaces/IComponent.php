<?php

namespace PhpSPA\Interfaces;

use PhpSPA\Core\Impl\RealImpl\ComponentImpl;

/**
 * Component interface for PhpSPA framework
 *
 * This interface defines the contract for all components within the PhpSPA
 * framework, including methods for component configuration, routing, and
 * rendering behavior. It ensures consistent component structure and behavior.
 *
 * @package PhpSPA\Interfaces
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 */
interface IComponent
{
    /**
     * Constructor for the Component class.
     *
     * Initializes the component with a callable that defines the component function.
     *
     * @param callable $component The callable representing the component logic.
     * @see https://phpspa.readthedocs.io/en/stable/core-concepts
     */
    public function __construct(callable $component);

    /**
     * Sets the title for the component.
     *
     * @param string $title The title to set.
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/routing/component-configuration
     */
    public function title(string $title): ComponentImpl;

    /**
     * Sets the method name for the component.
     *
     * @param string $method The name of the method to set, default to "GET|POST".
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/routing/component-configuration/#specifying-http-methods
     */
    public function method(string $method): ComponentImpl;

    /**
     * Sets the current route for the component.
     *
     * @param array|string $route The route to be set.
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/routing/advanced-routing
     */
    public function route(array|string $route): ComponentImpl;

    /**
     * Sets the target ID for the component.
     *
     * @param string $targetID The identifier of the target element.
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/routing/component-configuration/#setting-the-target-render-element
     */
    public function targetID(string $targetID): ComponentImpl;

    /**
     * Enables case sensitivity for the component.
     *
     * Sets the internal flag to treat operations as case sensitive.
     *
     * @return ComponentImpl Returns the current instance for method chaining.
     * @https://phpspa.readthedocs.io/en/stable/routing/component-configuration/#route-case-sensitivity
     */
    public function caseSensitive(): ComponentImpl;

    /**
     * Sets the component to operate in a case-insensitive mode.
     *
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/routing/component-configuration/#__tabbed_1_2
     */
    public function caseInsensitive(): ComponentImpl;

    /**
     * Sets the script to be executed when the component is mounted.
     *
     * @param callable $script The script to be executed.
     * @param string|null $name Optional name for the script asset.
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/performance/managing-styles-and-scripts/#component-specific-assets
    */
    public function script(callable $script, ?string $name = null): ComponentImpl;

    /**
     * Sets the stylesheet to be executed when the component is mounted.
     *
     * @param callable $style The stylesheet to be executed.
     * @param string|null $name Optional name for the stylesheet.
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/performance/managing-styles-and-scripts/#component-specific-assets
     */
    public function styleSheet(callable $style, ?string $name = null): ComponentImpl;

    /**
     * This sets the component to be called every particular interval given
     *
     * @param int $milliseconds
     * @return ComponentImpl Returns the current instance for method chaining.
     * @see https://phpspa.readthedocs.io/en/stable/requests/auto-reloading-components
     */
    public function reload(int $milliseconds): ComponentImpl;
}
