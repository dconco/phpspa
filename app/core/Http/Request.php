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
    * Invokes the request object to retrieve a parameter value by key.
    *
    * Checks if the specified key exists in the request parameters ($_REQUEST).
    * If found, validates and returns the associated value.
    * If not found, returns the provided default value.
    *
    * @param string $key The key to look for in the request parameters.
    * @param string|null $default The default value to return if the key does not exist. Defaults to null.
    * @return mixed The validated value associated with the key, or the default value if the key is not present.
    */
   public function __invoke (string $key, ?string $default = null): mixed
   {
      // Check if the key exists in the request parameters
      if (isset($_REQUEST[$key]))
      {
         // Validate and return the value associated with the key
         return $this->validate($_REQUEST[$key]);
      }

      // If the key does not exist, return the default value
      return $default;
   }
}