<?php

namespace phpSPA;

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