<?php

namespace phpSPA\Http;

use stdClass;

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
   use \phpSPA\Http\Auth\Authentication;

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

   /**
    * Retrieves file data from the request by name.
    *
    * This method retrieves file data from the request. If a name is provided, it returns the file data for that specific
    * input field; otherwise, it returns all file data as an object.
    *
    * @param ?string $name The name of the file input.
    * @return ?array File data, or null if not set.
    */
   public function files (?string $name = null): ?array
   {
      if (!$name)
      {
         return $_FILES;
      }
      if (!isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK)
      {
         return null;
      }

      return $_FILES[$name];
   }

   /**
    * Validates the API key from the request headers.
    *
    * @param string $key The name of the header containing the API key. Default is 'Api-Key'.
    * @return bool Returns true if the API key is valid, false otherwise.
    */
   public function apiKey (string $key = 'Api-Key')
   {
      return $this->validate(self::RequestApiKey($key));
   }

   /**
    * Retrieves authentication credentials from the request.
    *
    * This method retrieves the authentication credentials from the request, including both Basic Auth and Bearer token.
    * Returns an object with `basic` and `bearer` properties containing the respective credentials.
    *
    * @return stdClass The authentication credentials.
    */
   public function auth (): stdClass
   {
      $cl = new stdClass();
      $cl->basic = self::BasicAuthCredentials();
      $cl->bearer = self::BearerToken();

      return $cl;
   }

   /**
    * Parses and returns the query string parameters from the URL.
    *
    * This method parses the query string of the request URL and returns it as an object. If a name is specified,
    * it will return the specific query parameter value.
    *
    * @param ?string $name If specified, returns a specific query parameter by name.
    * @return mixed parsed query parameters or a specific parameter value.
    */
   public function urlQuery (?string $name = null)
   {
      if (php_sapi_name() == 'cli-server')
      {
         $parsed = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
      }
      else
      {
         $parsed = parse_url(
          $_REQUEST['uri'] ?? $_SERVER['REQUEST_URI'],
          PHP_URL_QUERY,
         );
      }

      $cl = new stdClass();

      if (!$parsed)
      {
         return $cl;
      }
      $parsed = mb_split('&', urldecode($parsed));

      $i = 0;
      while ($i < count($parsed))
      {
         $p = mb_split('=', $parsed[$i]);
         $key = $p[0];
         $value = $p[1] ? $this->validate($p[1]) : null;

         $cl->$key = $value;
         $i++;
      }

      if (!$name)
      {
         return $cl;
      }
      return $cl->$name;
   }


   /**
    * Retrieves the request body as an associative array.
    *
    * This method parses the raw POST body data and returns it as an associative array.
    * If a specific parameter is provided, it returns only that parameter's value.
    *
    * @param ?string $name The name of the body parameter to retrieve.
    * @return mixed The json data or null if parsing fails.
    */
   public function json (?string $name = null)
   {
      $data = json_decode(file_get_contents('php://input'), true);

      if ($data === null || json_last_error() !== JSON_ERROR_NONE)
      {
         return null;
      }

      if ($name !== null)
      {
         return $this->validate($data[$name]);
      }
      return $this->validate($data);
   }
}