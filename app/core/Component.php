<?php

namespace phpSPA;

class Component
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
   public ?string $title = null;

   /**
    * The HTTP method to be used for the component's request.
    * 
    * @var string $method Defaults to 'GET'.
    */
   public string $method = 'GET';

   /**
    * The route associated with the component to be rendered.
    *
    * @var string $route
    */
   public string $route;

   /**
    * The ID of the target element associated with this component.
    * This is typically used to specify where the component's content should be rendered in the DOM.
    * 
    * @var string|null The target element's ID, or null if to use the default target.
    */
   public ?string $targetID = null;


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