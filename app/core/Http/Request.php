<?php

namespace phpSPA\Http;

/**
 * Handles HTTP request data and provides methods to access request parameters,
 * headers, and other relevant information.
 *
 * This class is typically used to encapsulate all information about an incoming
 * HTTP request, such as GET, POST, and server variables.
 */
class Request
{
   use \phpSPA\Utils\Validate;


   /**
    * Handles the HTTP request and returns the request data.
    *
    * This method processes the incoming HTTP request and provides access
    * to request parameters, headers, and other relevant information.
    *
    * @return array The processed request data.
    */
   public function request ()
   {
      $keys = func_get_args();

      if (empty($keys))
      {
         return $this->validate($_REQUEST);
      }

      if (count($keys) > 1)
      {
         $data = [];

         foreach ($keys as $k)
         {
            if (isset($_REQUEST[$k]))
            {
               $data[$k] = $this->validate($_REQUEST[$k]);
            }
         }

         return $data;
      }

      return isset($_REQUEST[$keys[0]]) ? $this->validate($_REQUEST[$keys[0]]) : null;
   }

   /**
    * Invokes the request handler with the provided arguments.
    *
    * This magic method allows the object to be called as a function,
    * forwarding all received arguments to the internal request method.
    *
    * @return mixed The result of the request method.
    */
   public function __invoke ()
   {
      return $this->request(...func_get_args());
   }
}