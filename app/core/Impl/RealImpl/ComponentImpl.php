<?php

namespace PhpSPA\Core\Impl\RealImpl;

use PhpSPA\Interfaces\IComponent;

/**
 * Core component implementation class
 *
 * This abstract class provides the foundational implementation for components
 * within the PhpSPA framework. It handles component properties such as routes,
 * methods, titles, target IDs, and associated scripts and stylesheets.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @method IComponent title(string $title) Set the title of the component
 * @method IComponent method(string $method) Set the HTTP method for the component
 * @method IComponent route(array|string $route) Set the route(s) for the component
 * @method IComponent targetID(string $targetID) Set the target ID for the component
 * @method IComponent caseSensitive() Enable case sensitivity for the component
 * @method IComponent caseInsensitive() Disable case sensitivity for the component
 * @method IComponent script(callable $script, ?string $name = null) Add scripts to the component
 * @method IComponent styleSheet(callable $style, ?string $name = null) Add stylesheets to the component
 * @method IComponent reload(int $milliseconds) Set the reload interval for the component
 * @abstract
 */
abstract class ComponentImpl
{
    /**
     * The callable component that defines the behavior of this component.
     *
     * @var callable $component
     */
    protected $component;

    /**
     * The title of the component.
     * This can be used for display purposes, such as in a header or navigation.
     *
     * @var string|null $title The title can be a string or null if not set.
     */
    protected ?string $title = null;

    /**
     * The HTTP method to be used for the component's request.
     *
     * @var string $method Defaults to 'GET|VIEW'.
     */
    protected string $method = 'GET|VIEW';

    /**
     * The route associated with the component to be rendered.
     *
     * @var array|string $route
     */
    protected array|string $route;

    /**
     * The ID of the target element associated with this component.
     * This is typically used to specify where the component's content should be rendered in the DOM.
     *
     * @var string|null The target element's ID, or null if to use the default target.
     */
    protected ?string $targetID = null;

    /**
     * Indicates whether the component should treat values as case sensitive.
     *
     * @var bool|null If true, case sensitivity is enabled; if false, it is disabled; if null, the default behavior is used.
     */
    protected ?bool $caseSensitive = null;

    /**
     * The scripts to be executed when the component is mounted.
     * This can be used to add interactivity or dynamic behavior to the component.
     *
     * @var array<array{0: callable, 1: string|null}> $scripts
     */
    protected array $scripts = [];

    /**
     * The styles to be executed when the component is mounted.
     * This can be used to add interactivity or dynamic behavior to the component.
     *
     * @var array<array{0: callable, 1: string|null}> $stylesheets
     */
    protected array $stylesheets = [];

    /**
     * This registers the route to be called every particular interval provided.
     *
     * @var int $reloadTime
     */
    protected int $reloadTime = 0;

    /**
     * @param mixed $method
     * @param mixed $args
     * @throws \BadMethodCallException
     * @return IComponent
     */
    public function __call($method, $args): static
    {
        match ($method) {
            'title',
            'method',
            'route',
            'targetID' => $this->$method = $args[0],
            'reload' => $this->reloadTime = $args[0],
            'caseSensitive' => $this->caseSensitive = true,
            'caseInsensitive' => $this->caseSensitive = false,
            'styleSheet' => $this->stylesheets[] = $args,
            'script' => $this->scripts[] = $args,
            default => throw new \BadMethodCallException("Method {$method} does not exist in " . __CLASS__),
        };

        return $this;
    }
}
