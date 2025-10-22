<?php

namespace PhpSPA\Core\Client;

use BadMethodCallException;
use Exception;
use PhpSPA\Core\Client\HttpClientFactory;

/**
 * Enum representing HTTP request methods.
 *
 * This enumeration defines the standard HTTP request methods that can be used
 * when making HTTP requests through the client.
 *
 * @package Client
 */
enum RequestMethod {
   public const string GET = 'GET';
   public const string POST = 'POST';
   public const string PUT = 'PUT';
   public const string DELETE = 'DELETE';
   public const string PATCH = 'PATCH';
   public const string HEAD = 'HEAD';
   public const string OPTIONS = 'OPTIONS';
}


/**
 * PendingRequest class
 * 
 * Represents an HTTP request that is ready to be sent but has not yet been executed.
 * This class provides a fluent interface for building and configuring HTTP requests
 * with various HTTP methods.
 * 
 * Each method accepts either an array or string parameter:
 * - For GET, DELETE, HEAD, OPTIONS: query parameters
 * - For POST, PUT, PATCH: request body data
 * 
 * @package Client
 * @method ClientResponse get(array|string $query = null)
 * @method ClientResponse post(array|string $body = null)
 * @method ClientResponse put(array|string $body = null)
 * @method ClientResponse delete(array|string $query = null)
 * @method ClientResponse patch(array|string $body = null)
 * @method ClientResponse head(array|string $query = null)
 * @method ClientResponse options(array|string $query = null)
 */
class PendingRequest implements \ArrayAccess {
   private string $url;

   private ?string $data = null;

   private array $headers = ['Accept' => 'application/json'];

   private ?ClientResponse $response = null;

   private ?array $responseData = null;

   private HttpClient $client;

   /**
    * Constructs a new PendingRequest instance.
    *
    * @param string $url The URL for the pending request.
    */
   public function __construct (string $url)
   {
      $this->url = $url;
      $this->client = HttpClientFactory::create();
   }

   /**
    * Set the headers for the HTTP request.
    *
    * @param array $headers An associative array of HTTP headers to be sent with the request.
    *                       Keys represent header names and values represent header values.
    * @return PendingRequest
    */
   public function headers (array $headers): PendingRequest
   {
      $this->headers = array_merge($this->headers, $headers);
      return $this;
   }


   /**
    * Magic method to handle dynamic method calls on the PendingRequest instance.
    *
    * This method intercepts calls to undefined methods and allows for dynamic
    * method chaining or execution. It can return either a PendingRequest instance
    * for further chaining or a ClientResponse object when the request is executed.
    *
    * @param string $method The name of the method being called
    * @param array $args The arguments passed to the method
    * 
    * @return ClientResponse Returns a ClientResponse when the request is executed
    */
   public function __call ($method, $args): ClientResponse
   {
      $httpMethod = strtoupper($method);

      match ($httpMethod) {
         // GET, DELETE, HEAD, and OPTIONS use query params
         RequestMethod::GET,
         RequestMethod::DELETE,
         RequestMethod::HEAD,
         RequestMethod::OPTIONS => $this->buildQueryParams($args),

         // POST, PUT, and PATCH use a data body
         RequestMethod::POST,
         RequestMethod::PUT,
         RequestMethod::PATCH => $this->data = is_array($args[0]) ? json_encode($args[0]) : ($args[0] ?? null),
         default => throw new BadMethodCallException("Method $method does not exist.")
      };

      $this->response = $this->buildOptions($httpMethod);
      return $this->response;
   }


   /**
    * Helper to lazy-load the GET request.
    */
   private function fetchDataIfNeeded(): void
   {
      // If no method has been called yet, execute a default GET
      if ($this->response === null) {
         $this->get(); 
      }

      // If we don't have parsed JSON data yet, parse it
      if ($this->responseData === null) {
         $this->responseData = $this->response->json();
      }
   }

   public function __toString(): string
   {
      $this->fetchDataIfNeeded();
      return $this->response->text() ?: '';
   }

   public function offsetExists($offset): bool
   {
      $this->fetchDataIfNeeded();
      return isset($this->responseData[$offset]);
   }

   public function offsetGet($offset): mixed
   {
      $this->fetchDataIfNeeded();
      return $this->responseData[$offset] ?? null;
   }

   public function offsetSet($_, $__): void
   {
      throw new Exception("Response data is read-only.");
   }

   public function offsetUnset($_): void
   {
      throw new Exception("Response data is read-only.");
   }
   
   public function __isset($name): bool
   {
      $this->fetchDataIfNeeded();
      return isset($this->responseData[$name]);
   }
   
   public function __get($name): mixed
   {
      $this->fetchDataIfNeeded();
      return $this->responseData[$name] ?? null;
   }

   public function __set($_, $__): void
   {
      throw new Exception("Response data is read-only.");
   }

   public function __unset($_): void
   {
      throw new Exception("Response data is read-only.");
   }

   /**
    * Execute the HTTP request.
    *
    * @param string $httpMethod The HTTP method to use
    * @return ClientResponse The response object containing the HTTP response data
    */
   private function execute(string $httpMethod): ClientResponse
   {
      return $this->client->request(
         $this->url,
         $httpMethod,
         $this->headers,
         $this->data
      );
   }


   /**
    * Builds query parameters from the provided arguments.
    *
    * This method processes the given arguments and constructs query parameters
    * that will be appended to the HTTP request URL.
    *
    * @param array $args The arguments to be converted into query parameters.
    *                           Can be either an associative array of key-value pairs
    *                           or a pre-formatted query string.
    * 
    * @return void
    */
   private function buildQueryParams (array $args): void
   {
      // --- 1. Handle Array ---
      if (is_array($args[0] ?? null)) {
         $queryArray = $args[0];

      }
      // --- 2. Handle String ---
      else if (is_string($args[0] ?? null)) {
         $queryArray = [];
         parse_str(ltrim($args[0], '/?&'), $queryArray);

      }
      // --- Nothing to do ---
      else {
         return;
      }

      // --- 3. Build Safely ---
      $queryString = http_build_query($queryArray); // This does all encoding correctly

      if (!empty($queryString)) {
         $separator = strpos($this->url, '?') === false ? '?' : '&';
         $this->url .= $separator . $queryString;
      }
   }



   /**
    * Builds and returns the response for the pending request.
    *
    * @param string $httpMethod The HTTP method for which to build the response
    * @return ClientResponse The response object
    */
   private function buildOptions (string $httpMethod): ClientResponse
   {
      if ($this->data !== null) {
         $this->headers = array_merge($this->headers, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($this->data),
         ]);
      }

      return $this->execute($httpMethod);
   }
}
