<?php

namespace phpSPA\Interfaces;

use phpSPA\Core\Impl\RealImpl\ComponentImpl;

/**
 * Component interface for phpSPA framework
 *
 * This interface defines the contract for all components within the phpSPA
 * framework, including methods for component configuration, routing, and
 * rendering behavior. It ensures consistent component structure and behavior.
 *
 * @package phpSPA\Interfaces
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
     */
    public function __construct(callable $component);

    /**
     * Sets the title for the component.
     *
     * @param string $title The title to set.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function title(string $title): ComponentImpl;

    /**
     * Sets the method name for the component.
     *
     * @param string $method The name of the method to set.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function method(string $method): ComponentImpl;

    /**
     * Sets the current route for the component.
     *
     * @param array|string $route The route to be set.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function route(array|string $route): ComponentImpl;

    /**
     * Sets the target ID for the component.
     *
     * @param string $targetID The identifier of the target element.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function targetID(string $targetID): ComponentImpl;

    /**
     * Enables case sensitivity for the component.
     *
     * Sets the internal flag to treat operations as case sensitive.
     *
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function caseSensitive(): ComponentImpl;

    /**
     * Sets the component to operate in a case-insensitive mode.
     *
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function caseInsensitive(): ComponentImpl;

    /**
     * Sets the script to be executed when the component is mounted.
     *
     * @param callable $script The script to be executed.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function script(callable $script): ComponentImpl;

    /**
     * Sets the stylesheet to be executed when the component is mounted.
     *
     * @param callable $style The stylesheet to be executed.
     * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function styleSheet(callable $style): ComponentImpl;

    /**
     * This sets the component to be called every particular interval given
     *
     * @param int $milliseconds
      * @return ComponentImpl Returns the current instance for method chaining.
     */
    public function reload(int $milliseconds): ComponentImpl;
}
