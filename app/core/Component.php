<?php

namespace phpSPA;

/**
 * Class Component
 *
 * This class serves as the core component within the phpSPA framework, providing
 * essential functionality and structure for all components.
 *
 * @package phpSPA
 * @extends \phpSPA\Impl\RealImpl\ComponentImpl
 * @implements \phpSPA\Interfaces\IComponent
 */
class Component extends \phpSPA\Impl\RealImpl\ComponentImpl implements \phpSPA\Interfaces\IComponent
{
   /**
    * Constructor for the Component class.
    *
    * Initializes the component with a callable that defines the component function.
    *
    * @param callable $component The callable representing the component logic.
    */
   public function __construct (callable $component)
   {
      $this->component = $component;
   }
}