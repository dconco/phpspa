<?php

namespace phpSPA\Interfaces;

interface IComponent
{
   /**
    * Constructor for the Component class.
    *
    * Initializes the component with a callable that defines the component function.
    *
    * @param callable $component The callable representing the component logic.
    */
   public function __construct (callable $component);

   /**
    * Sets the title for the component.
    *
    * @param string $title The title to set.
    * @return self Returns the current instance for method chaining.
    */
   public function title (string $title): self;

   /**
    * Sets the method name for the component.
    *
    * @param string $method The name of the method to set.
    * @return self Returns the current instance for method chaining.
    */
   public function method (string $method): self;

   /**
    * Sets the current route for the component.
    *
    * @param array|string $route The route to be set.
    * @return self Returns the current instance for method chaining.
    */
   public function route (array|string $route): self;

   /**
    * Sets the target ID for the component.
    *
    * @param string $targetID The identifier of the target element.
    * @return self Returns the current instance for method chaining.
    */
   public function targetID (string $targetID): self;

   /**
    * Enables case sensitivity for the component.
    *
    * Sets the internal flag to treat operations as case sensitive.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function caseSensitive (): self;

   /**
    * Sets the component to operate in a case-insensitive mode.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function caseInsensitive (): self;

   /**
    * Sets the script to be executed when the component is mounted.
    *
    * @param callable $script The script to be executed.
    * @return self Returns the current instance for method chaining.
    */
   public function script (callable $script): self;

   /**
    * Sets the stylesheet to be executed when the component is mounted.
    *
    * @param callable $style The stylesheet to be executed.
    * @return self Returns the current instance for method chaining.
    */
   public function styleSheet (callable $style): self;
}