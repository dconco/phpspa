<?php

namespace phpSPA;

/**
 * Core component class for phpSPA framework.
 *
 * Provides essential functionality for component rendering, lifecycle management,
 * and state handling. Supports class components with __render method and namespace
 * organization.
 *
 * @author dconco <concodave@gmail.com>
 * @see https://phpspa.readthedocs.io/ Component Documentation
 */
class Component extends \phpSPA\Core\Impl\RealImpl\ComponentImpl implements \phpSPA\Interfaces\IComponent
{
    /**
     * Constructor for the Component class.
     *
     * Initializes the component with a callable that defines the component function.
     *
     * @param callable $component The callable representing the component logic.
     */
    public function __construct(callable $component)
    {
        $this->component = $component;
    }
}
