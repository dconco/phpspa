<?php

namespace phpSPA;

class App implements Interfaces\phpSpaInterface
{
   /**
    * The layout of the application.
    *
    * @var callable $layout
    */
   protected $layout;

   /**
    * The target ID where the application will render its content.
    *
    * @var string $targetID
    */
   protected string $targetID;

   /**
    * Stores the list of application components.
    * Each component can be accessed and managed by the application core.
    * Typically used for dependency injection or service management.
    * 
    * @var Component[] $components
    */
   protected array $components = [];

   /**
    * APP CONSTRUCTOR
    */
   public function __construct (callable $layout)
   {
      $this->layout = $layout;
   }

   /**
    * APPLICATION INITIAL TARGET ID
    */
   public function targetID (string $targetID): void
   {
      $this->targetID = $targetID;
   }

   /**
    * ATTACH COMPONENT
    */
   public function attach (Component $component): void
   {
      $this->components[] = $component;
   }

   /**
    * DETACH COMPONENT
    */
   public function detach (Component $component): void
   {
      $key = array_search($component, $this->components, true);

      if ($key !== false)
      {
         unset($this->components[$key]);
      }
   }

   /**
    * RUN APPLICATION
    */
   public function run (): void
   {
   }
}