<?php

namespace PhpSPA\Core\Client;

use BadMethodCallException;
use Exception;
use PhpSPA\Core\Client\HttpClientFactory;

/**
 * class representing HTTP request methods.
 *
 * This class defines the standard HTTP request methods that can be used
 * when making HTTP requests through the client.
 *
 * @package Client
 */
class RequestMethod {
   public const GET = 'GET';
   public const POST = 'POST';
   public const PUT = 'PUT';
   public const DELETE = 'DELETE';
   public const PATCH = 'PATCH';
   public const HEAD = 'HEAD';
   public const OPTIONS = 'OPTIONS';
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
 * When async() is called before the HTTP method, the return type changes to AsyncResponse.
 * Otherwise, it returns ClientResponse for synchronous execution.
 * 
 * @since v2.0.1
 * @package Client
 * @see https://phpspa.tech/references/hooks/use-fetch
 * @method ClientResponse|AsyncResponse get(array|string $query = null)
 * @method ClientResponse|AsyncResponse post(array|string $body = null)
 * @method ClientResponse|AsyncResponse put(array|string $body = null)
 * @method ClientResponse|AsyncResponse delete(array|string $query = null)
 * @method ClientResponse|AsyncResponse patch(array|string $body = null)
 * @method ClientResponse|AsyncResponse head(array|string $query = null)
 * @method ClientResponse|AsyncResponse options(array|string $query = null)
 */
class PendingRequest implements \ArrayAccess {
   private string $url;

   private ?string $data = null;

   private array $headers = ['Accept' => ['application/json', 'text/html', 'application/xml']];

   private ?ClientResponse $response = null;

   private array|ClientResponse|null $responseData = null;

   private HttpClient $client;

   private array $options = [];

   private bool $async = false;

   private ?string $method = null;

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
      foreach ($headers as $key => $value) {
         $this->headers[$key] = $value;
      }
      return $this;
   }

   /**
    * Set request timeout in seconds.
    *
    * @param int $seconds Timeout in seconds
    * @return PendingRequest
    */
   public function timeout(int $seconds): PendingRequest
   {
      $this->options['timeout'] = $seconds;
      return $this;
   }

   /**
    * Set connection timeout in seconds.
    *
    * Note: Only available when cURL is enabled. Ignored with PHP streams fallback.
    *
    * @param int $seconds Connection timeout in seconds
    * @return PendingRequest
    */
   public function connectTimeout(int $seconds): PendingRequest
   {
      $this->options['connect_timeout'] = $seconds;
      return $this;
   }

   public function unixSocketPath(string $path): PendingRequest
   {
      $this->options['unix_socket_path'] = $path;
      return $this;
   }

   /**
    * Set the Unix domain socket path to be used for this pending request.
    *
    * This configures the underlying HTTP client/transport to connect via a Unix socket
    * instead of a TCP host:port. The provided path should point to an existing socket
    * file (e.g. "/var/run/service.sock").
    *
    * @param string $path Absolute or relative filesystem path to the Unix socket file.
    * @return PendingRequest Returns the current instance for fluent chaining.
    */
   public function unixSocket(string $path): PendingRequest
   {
      $this->options['unix_socket_path'] = $path;
      return $this;
   }

   /**
    * Set custom options for the request.
    *
    * Supports both PhpSPA-defined options (like `timeout`, `user_agent`, etc.)
    * and raw cURL options for advanced usage.
    *
    * Raw cURL options can be provided in any of these forms:
    *
    * - `withOptions([CURLOPT_PROXY => 'http://...', CURLOPT_TIMEOUT => 5])`
    * - `withOptions(['CURLOPT_PROXY' => 'http://...'])` (constant name string)
    * - `withOptions(['curl' => [CURLOPT_PROXY => 'http://...']])`
    *
    * When cURL is not available, raw cURL options are ignored by the stream fallback client.
    *
    * @param array $options Custom options array
    * @return PendingRequest
    */
   public function withOptions(array $options): PendingRequest
   {
      foreach ($options as $key => $value) {
         // Allow nesting: ['curl' => [CURLOPT_* => ...]]
         if (($key === 'curl' || $key === 'curl_options') && \is_array($value)) {
            $this->options['curl'] ??= [];
            foreach ($value as $curlKey => $curlValue) {
               if (\is_string($curlKey) && str_starts_with($curlKey, 'CURLOPT_') && \defined($curlKey)) {
                  $curlKey = \constant($curlKey);
               }
               if (\is_int($curlKey)) {
                  $this->options['curl'][$curlKey] = $curlValue;
               }
            }
            continue;
         }

         // Allow passing CURLOPT_* directly at top-level
         if (\is_int($key)) {
            $this->options['curl'] ??= [];
            $this->options['curl'][$key] = $value;
            continue;
         }

         // Allow passing CURLOPT_* by constant name string at top-level
         if (\is_string($key) && str_starts_with($key, 'CURLOPT_') && \defined($key)) {
            $this->options['curl'] ??= [];
            $this->options['curl'][\constant($key)] = $value;
            continue;
         }

         // Default: treat as PhpSPA option
         $this->options[$key] = $value;
      }
      return $this;
   }

   /**
    * Enable or disable SSL verification.
    *
    * @param bool $verify Whether to verify SSL certificates
    * @return PendingRequest
    */
   public function verifySSL(bool $verify = true): PendingRequest
   {
      $this->options['verify_ssl'] = $verify;
      return $this;
   }

   /**
    * Sets the IP resolution strategy for this pending request.
    *
    * @param string $ip The IP version to resolve: 'v4' or 'v6'.
    *
    * @return PendingRequest Returns the current instance for fluent chaining.
    *
    * @throws \InvalidArgumentException If $ip is not 'v4' or 'v6'.
    */
   public function resolveIP(string $ip): PendingRequest
   {
      if ($ip !== 'v4' && $ip !== 'v6') {
         throw new \InvalidArgumentException("IP must either be v4 or v6", 1);
      }

      $this->options['ip_resolve'] = $ip;
      return $this;
   }

   /**
    * Set path to CA certificate bundle.
    *
    * @param string $path Path to certificate file
    * @return PendingRequest
    */
   public function withCertificate(string $path): PendingRequest
   {
      $this->options['cert_path'] = $path;
      return $this;
   }

   /**
    * Set custom User-Agent header.
    *
    * @param string $userAgent User-Agent string
    * @return PendingRequest
    */
   public function withUserAgent(string $userAgent): PendingRequest
   {
      $this->options['user_agent'] = $userAgent;
      return $this;
   }

   /**
    * Enable or disable following redirects.
    *
    * Note: Only available when cURL is enabled. Ignored with file_get_contents fallback.
    *
    * @param bool $follow Whether to follow redirects
    * @param int $maxRedirects Maximum number of redirects to follow
    * @return PendingRequest
    */
   public function followRedirects(bool $follow = true, int $maxRedirects = 10): PendingRequest
   {
      $this->options['follow_redirects'] = $follow;
      $this->options['max_redirects'] = $maxRedirects;
      return $this;
   }

   /**
    * Enable asynchronous request execution.
    *
    * Note: Only available when cURL is enabled. Returns AsyncResponse instead of ClientResponse.
    *
    * @return PendingRequest
    */
   public function async(): PendingRequest
   {
      $this->async = true;
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
    * @return ClientResponse|AsyncResponse Returns a ClientResponse or AsyncResponse when the request is executed
    */
   public function __call ($method, $args): ClientResponse|AsyncResponse
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
         RequestMethod::PATCH => $this->data = \is_array($args[0]) ? json_encode($args[0]) : ($args[0] ?? null),
         default => throw new BadMethodCallException("Method $method does not exist.")
      };

      $this->method = $httpMethod;
      return $this->buildOptions($httpMethod);
   }


   /**
    * Helper to lazy-load the GET request.
    */
   private function fetchDataIfNeeded(): void
   {
      // If no method has been called yet, execute a default GET
      if ($this->response === null) {
         $this->response = $this->get(); 
      }
      
      // If we don't have parsed JSON data yet, parse it
      if ($this->responseData === null) {
         $this->responseData = $this->response?->json() ?? $this->response;
      }
   }

   public function __toString(): string
   {
      $this->fetchDataIfNeeded();
      return $this->response?->text() ?? false;
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
   private function execute(string $httpMethod): ClientResponse|AsyncResponse
   {
      // If async mode is enabled and client is CurlHttpClient, return AsyncResponse
      if ($this->async && $this->client instanceof CurlHttpClient) {
         $handle = $this->client->prepareAsync(
            $this->url,
            $httpMethod,
            $this->headers,
            $this->data,
            $this->options
         );
         return new AsyncResponse($handle);
      }

      // Synchronous execution
      return $this->client->request(
         $this->url,
         $httpMethod,
         $this->headers,
         $this->data,
         $this->options
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
      if (\is_array($args[0] ?? null)) {
         $queryArray = $args[0];
         
      }
      // --- 2. Handle String ---
      else if (\is_string($args[0] ?? null)) {
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
   private function buildOptions (string $httpMethod): ClientResponse|AsyncResponse
   {
      return $this->execute($httpMethod);
   }
}
