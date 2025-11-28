<?php

namespace PhpSPA;

/**
 * Core component class for PhpSPA framework.
 *
 * Provides essential functionality for component rendering, lifecycle management,
 * and state handling. Supports class components with __render method and namespace
 * organization.
 *
 * @author dconco <me@dconco.tech>
 */
class Component extends \PhpSPA\Core\Impl\RealImpl\ComponentImpl
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
