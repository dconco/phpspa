<?php

namespace PhpSPA\Core\Impl\RealImpl;

/**
 * Core component implementation class
 *
 * This abstract class provides the foundational implementation for components
 * within the PhpSPA framework. It handles component properties such as routes,
 * methods, titles, target IDs, and associated scripts and stylesheets.
 *
 * @package PhpSPA\Core\Impl\RealImpl
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @var callable $component
 * @var ?string $title
 * @var string $method GET|VIEW
 * @var string $route
 * @var ?string $targetID
 * @var ?string $caseSensitive
 * @var callable[] $scripts
 * @var callable[] $stylesheets
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

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function method(string $method): self
    {
        $this->method = $method;

        if (strtolower($_SERVER['REQUEST_METHOD']) === 'phpspa_get') {
            $this->method = $method . '|' . strtoupper('phpspa_get');
        }
        return $this;
    }

    public function route(array|string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function targetID(string $targetID): self
    {
        $this->targetID = $targetID;
        return $this;
    }

    public function caseSensitive(): self
    {
        $this->caseSensitive = true;
        return $this;
    }

    public function caseInsensitive(): self
    {
        $this->caseSensitive = false;
        return $this;
    }

    public function script(callable $script, ?string $name = null): self
    {
        $this->scripts[] = [$script, $name];
        return $this;
    }

    public function styleSheet(callable $style, ?string $name = null): self
    {
        $this->stylesheets[] = [$style, $name];
        return $this;
    }

    public function reload(int $milliseconds): self
    {
        $this->reloadTime = $milliseconds;
        return $this;
    }
}
