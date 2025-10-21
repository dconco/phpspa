<?php

namespace PhpSPA\Client;

use BadMethodCallException;
use Exception;

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
 * @method PendingRequest get(array|string $query = null)
 * @method PendingRequest post(array|string $body = null)
 * @method PendingRequest put(array|string $body = null)
 * @method PendingRequest delete(array|string $query = null)
 * @method PendingRequest patch(array|string $body = null)
 * @method PendingRequest head(array|string $query = null)
 * @method PendingRequest options(array|string $query = null)
 */
class PendingRequest implements \ArrayAccess {
   private string $url;

   private ?string $data = null;

   private array $headers = ['Accept' => 'application/json'];

   private ?ClientResponse $response = null;

   private ?array $responseData = null;

   /**
    * Constructs a new PendingRequest instance.
    *
    * @param string $url The URL for the pending request.
    */
   public function __construct (string $url)
   {
      $this->url = $url;
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
    * Execute the HTTP request with the given options.
    *
    * This method performs the actual HTTP request using the provided options array
    * and returns a ClientResponse object containing the result of the request.
    *
    * @param array $options An associative array of request options (headers, body, method, etc.)
    * 
    * @return ClientResponse The response object containing the HTTP response data
    * 
    * @throws \RuntimeException If the request execution fails
    * @throws \InvalidArgumentException If the options array is invalid
    */
   private function execute (array $options): ClientResponse
   {
      $context = stream_context_create($options);
      $body = @file_get_contents($this->url, false, $context);

      // $http_response_header is a magic variable populated by file_get_contents
      $responseHeaders = $http_response_header ?? [];

      $statusCode = 0;
      if (isset($responseHeaders[0])) {
         // e.g., "HTTP/1.1 200 OK"
         @list( , $statusCode, ) = explode(' ', $responseHeaders[0], 3);
      }

      return new ClientResponse($body, (int) $statusCode, $responseHeaders);
   }

   /**
    * Builds a formatted header string from an array of headers.
    *
    * Converts an associative array of HTTP headers into a properly formatted
    * header string suitable for use in HTTP requests.
    *
    * @param array $headers An associative array of header names and values
    *                       where keys are header names and values are header values
    * @return string A formatted header string with each header on a new line
    */
   private function buildHeaderString (array $headers): string
   {
      $lines = [];
      foreach ($headers as $key => $value) {
         $lines[] = "$key: $value";
      }
      return implode("\r\n", $lines);
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
    * Builds and returns the OPTIONS response for the pending request.
    *
    * This method constructs an HTTP OPTIONS response, typically used to describe
    * the communication options available for the target resource or server.
    *
    * @param string $httpMethod The HTTP method for which to build the OPTIONS response
    * @return ClientResponse The OPTIONS response object containing allowed methods and headers
    */
   private function buildOptions (string $httpMethod): ClientResponse
   {
      $options = [
         'http' => [
            'method' => $httpMethod,
            'header' => $this->buildHeaderString($this->headers),
            'ignore_errors' => true,
         ]
      ];

      if ($this->data !== null) {
         $headers = array_merge($this->headers, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($this->data),
         ]);

         $options['http']['content'] = $this->data;
         $options['http']['header'] = $this->buildHeaderString($headers);
      }

      return $this->execute($options);
   }
}
