<?php

namespace phpSPA\Impl\RealImpl;

class ComponentImpl
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
    * @var string $method Defaults to 'GET'.
    */
   protected string $method = 'GET';

   /**
    * The route associated with the component to be rendered.
    *
    * @var string $route
    */
   protected string $route;

   /**
    * The ID of the target element associated with this component.
    * This is typically used to specify where the component's content should be rendered in the DOM.
    * 
    * @var string|null The target element's ID, or null if to use the default target.
    */
   protected ?string $targetID = null;


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

   /**
    * Sets the title for the component.
    *
    * @param string $title The title to set.
    * @return self Returns the current instance for method chaining.
    */
   public function title (string $title): self
   {
      $this->title = $title;
      return $this;
   }

   /**
    * Sets the method name for the component.
    *
    * @param string $method The name of the method to set.
    * @return self Returns the current instance for method chaining.
    */
   public function method (string $method): self
   {
      $this->method = $method;
      return $this;
   }

   /**
    * Sets the current route for the component.
    *
    * @param string $route The route to be set.
    * @return self Returns the current instance for method chaining.
    */
   public function route (string $route): self
   {
      $this->route = $route;
      return $this;
   }

   /**
    * Sets the target ID for the component.
    *
    * @param string $targetID The identifier of the target element.
    * @return self Returns the current instance for method chaining.
    */
   public function targetID (string $targetID): self
   {
      $this->targetID = $targetID;
      return $this;
   }
}