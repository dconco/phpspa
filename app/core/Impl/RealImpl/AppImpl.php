<?php

namespace phpSPA\Impl\RealImpl;

use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Router\MapRoute;
use phpSPA\Helper\LinkTagFormatter;
use phpSPA\Helper\CallableInspector;

abstract class AppImpl extends Component
{
   /**
    * The layout of the application.
    *
    * @var callable $layout
    */
   protected $layout;

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
    * Holds the data that has been rendered.
    *
    * This property is used to store data that has already been processed or rendered
    * by the application, allowing for reuse or reference without reprocessing.
    *
    * @var mixed
    */
   private $renderedData;

   public function defaultTargetID (string $targetID): void
   {
      $this->defaultTargetID = $targetID;
   }

   public function defaultToCaseSensitive (): void
   {
      $this->defaultCaseSensitive = true;
   }

   public function attach (Component $component): void
   {
      $this->components[] = $component;
   }

   public function detach (Component $component): void
   {
      $key = array_search($component, $this->components, true);

      if ($key !== false)
      {
         unset($this->components[$key]);
      }
   }

   public function run (): void
   {
      foreach ($this->components as $component)
      {
         $caseSensitive = $component->caseSensitive ?? $this->defaultCaseSensitive;
         $targetID = $component->targetID ?? $this->defaultTargetID;

         $router = (new MapRoute())
         ->match($component->method, $component->route, $caseSensitive);

         if (!$router)
         {
            continue; // Skip if no match found
         }

         $request = new Request();

         $layoutOutput = call_user_func($this->layout);
         $componentOutput = '';


         if (strtolower($_SERVER['REQUEST_METHOD']) === 'phpspa_get')
         {
            $body = json_decode(file_get_contents('php://input'), true);

            if ($body !== null && json_last_error() === JSON_ERROR_NONE)
            {
               if (!empty($body['stateKey']) && !empty($body['value']))
               {
                  if (session_status() < 2) session_start();
                  $_SESSION["__phpspa_state_{$body['stateKey']}"] = $body['value'];
               }
            }
         }
         else
         {
            if (session_status() < 2) session_start();
            $reg = unserialize($_SESSION["__registered_phpspa_states"] ?? serialize([]));
            foreach ($reg as $r)
            {
               unset($_SESSION["__phpspa_state_$r"]);
            }
         }

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
            $componentOutput = call_user_func($component->component, path: $router['params'], request: $request);
         }
         elseif (CallableInspector::hasParam($component->component, 'path'))
         {
            $componentOutput = call_user_func($component->component, path: $router['params']);
         }
         elseif (CallableInspector::hasParam($component->component, 'request'))
         {
            $componentOutput = call_user_func($component->component, request: $request);
         }
         else
         {
            $componentOutput = call_user_func($component->component);
         }

         $componentOutput = LinkTagFormatter::format($componentOutput);

         // If the component has a script, execute it
         if (!empty($component->scripts))
         {
            foreach ($component->scripts as $script)
            {
               if (is_callable($script))
               {
                  $scriptValue = call_user_func($script);

                  if (is_string($scriptValue) && !empty($scriptValue))
                  {
                     $componentOutput .= "\n<script data-type=\"phpspa/script\">\n" . $scriptValue . "\n</script>\n";
                  }
               }
            }
         }

         // If the component has a style, execute it
         if (!empty($component->stylesheets))
         {
            foreach ($component->stylesheets as $style)
            {
               if (is_callable($style))
               {
                  $styleValue = call_user_func($style);

                  if (is_string($styleValue) && !empty($styleValue))
                  {
                     $componentOutput = "<style data-type=\"phpspa/css\">\n" . $styleValue . "\n</style>\n" . $componentOutput;
                  }
               }
            }
         }

         if (strtolower($_SERVER['REQUEST_METHOD']) === 'phpspa_get')
         {
            $info = [ 'content' => $componentOutput, 'title' => $component->title, 'targetID' => $targetID ];
            print_r(json_encode($info));
         }
         else
         {
            $layoutOutput = LinkTagFormatter::format($layoutOutput);

            if ($component->title)
            {
               $layoutOutput = preg_replace_callback(
                 '/<title([^>]*)>.*?<\/title>/si',
                 function ($matches) use ($component)
                 {
                    // $matches[1] contains any attributes inside the <title> tag
                    return '<title' . $matches[1] . '>' . $component->title . '</title>';
                 },
                 $layoutOutput,
               );
            }

            $this->renderedData = str_replace('__CONTENT__', "\n<div data-phpspa-target>" . $componentOutput . "</div>\n", $layoutOutput);
            print_r($this->renderedData);
         }

         exit;
      }
   }
}