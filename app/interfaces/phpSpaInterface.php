<?php

namespace phpSPA\Interfaces;

use phpSPA\Component;

interface phpSpaInterface
{
   /**
    * App constructor.
    *
    * Initializes the App instance with the specified layout.
    *
    * @param string $layout The name of the layout to be used by the application.
    */
   public function __construct (string $layout);

   /**
    * Sets the target ID for the application.
    *
    * @param string $targetID The target ID to be set.
    *
    * @return void
    */
   public function targetID (string $targetID): void;

   /**
    * Attaches a component to the current object.
    *
    * @param Component $component The component instance to attach.
    * @return void
    */
   public function attach (Component $component): void;
}