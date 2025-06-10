<?php

namespace phpSPA\Impl\RealImpl;

use phpSPA\Component;

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
   protected string $route;

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

   public function title (string $title): Component
   {
      $this->title = $title;
      return $this;
   }

   public function method (string $method): Component
   {
      $this->method = $method;

      if (strtolower($_SERVER['REQUEST_METHOD']) === 'phpspa_get')
      {
         $this->method = $method . '|' . strtoupper('phpspa_get');
      }
      return $this;
   }

   public function route (array|string $route): Component
   {
      $this->route = $route;
      return $this;
   }

   public function targetID (string $targetID): Component
   {
      $this->targetID = $targetID;
      return $this;
   }

   public function caseSensitive (): Component
   {
      $this->caseSensitive = true;
      return $this;
   }

   public function caseInsensitive (): Component
   {
      $this->caseSensitive = false;
      return $this;
   }
}