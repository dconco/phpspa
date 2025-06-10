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
    * @param callable $layout The name of the layout to be used by the application.
    */
   public function __construct (callable $layout);

   /**
    * Sets the target ID for the application.
    *
    * @param string $targetID The target ID to be set.
    *
    * @return void
    */
   public function defaultTargetID (string $targetID): void;

   /**
    * Sets the default behavior to case sensitive.
    *
    * Implementing this method should configure the system or component
    * to treat relevant operations (such as string comparisons or lookups)
    * as case sensitive by default.
    *
    * @return void
    */
   public function defaultToCaseSensitive (): void;

   /**
    * Attaches a component to the current object.
    *
    * @param Component $component The component instance to attach.
    * @return void
    */
   public function attach (Component $component): void;

   /**
    * Detaches the specified component from the current context.
    *
    * @param Component $component The component instance to be detached.
    * @return void
    */
   public function detach (Component $component): void;

   /**
    * Runs the application.
    *
    * This method is responsible for executing the main logic of the application,
    * including routing, rendering components, and managing the application lifecycle.
    *
    * @return void
    */
   public function run (): void;
}