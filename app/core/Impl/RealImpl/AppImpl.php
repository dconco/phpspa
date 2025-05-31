<?php

namespace phpSPA\Impl\RealImpl;

use Closure;
use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Router\MapRoute;
use phpSPA\Helper\CallableInspector;

class AppImpl extends Component implements \phpSPA\Interfaces\phpSpaInterface
{
   /**
    * The layout of the application.
    *
    * @var callable $layout
    */
   private $layout;

   /**
    * The default target ID where the application will render its content.
    *
    * @var string $defaultTargetID
    */
   private string $defaultTargetID;

   /**
    * Stores the list of application components.
    * Each component can be accessed and managed by the application core.
    * Typically used for dependency injection or service management.
    * 
    * @var Component[] $components
    */
   private array $components = [];

   /**
    * Indicates whether the application should treat string comparisons as case sensitive.
    *
    * @var bool $defaultCaseSensitive Defaults to false, meaning string comparisons are case insensitive by default.
    */
   private bool $defaultCaseSensitive = false;

   /**
    * The base URI of the application.
    * This is used to determine the root path for routing and resource loading.
    *
    * @var string
    */
   public static string $request_uri;


   /**
    * APP CONSTRUCTOR
    */
   public function __construct (callable $layout)
   {
      $this->layout = $layout;
      self::$request_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
   }

   /**
    * APPLICATION INITIAL TARGET ID
    */
   public function defaultTargetID (string $targetID): void
   {
      $this->defaultTargetID = $targetID;
   }

   public function defaultToCaseSensitive (): void
   {
      $this->caseSensitive = true;
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
      foreach ($this->components as $component)
      {
         $caseSensitive = $component->caseSensitive ?? $this->defaultCaseSensitive;

         $router = (new MapRoute())
            ->match($component->method, $component->route, $caseSensitive);

         if (!$router)
         {
            continue; // Skip if no match found
         }

         $request = new Request();

         /**
          * Invokes the specified component callback with appropriate parameters based on its signature.
          *
          * This logic checks if the component's callable accepts 'path' and/or 'request' parameters
          * using CallableInspector. It then calls the component with the corresponding arguments:
          * - If both 'path' and 'request' are accepted, both are passed.
          * - If only 'path' is accepted, only 'path' is passed.
          * - If only 'request' is accepted, only 'request' is passed.
          * - If neither is accepted, the component is called without arguments.
          *
          * @param object $component The component object containing the callable to invoke.
          * @param array $router An associative array containing 'params' and 'request' to be passed as arguments.
          */

         if (CallableInspector::hasParam($component->component, 'path') && CallableInspector::hasParam($component->component, 'request'))
         {
            call_user_func($component->component, path: $router['params'], request: $request);
         }
         elseif (CallableInspector::hasParam($component->component, 'path'))
         {
            call_user_func($component->component, path: $router['params']);
         }
         elseif (CallableInspector::hasParam($component->component, 'request'))
         {
            call_user_func($component->component, request: $request);
         }
         else
         {
            call_user_func($component->component);
         }
      }
   }
}