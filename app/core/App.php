<?php

namespace phpSPA;

class App implements Interfaces\phpSpaInterface
{
   /**
    * The layout of the application.
    *
    * @var string
    */
   protected string $layout;

   /**
    * The target ID where the application will render its content.
    *
    * @var string
    */
   protected string $targetID;

   /**
    * @var array $components
    * 
    * Stores the list of application components.
    * Each component can be accessed and managed by the application core.
    * Typically used for dependency injection or service management.
    */
   protected array $components = [];

   public function __construct (string $layout)
   {
      $this->layout = $layout;
   }

   public function targetID (string $targetID): void
   {
      $this->targetID = $targetID;
   }

   public function attach (Component $component): void
   {
      $this->components[] = $component;
   }
}