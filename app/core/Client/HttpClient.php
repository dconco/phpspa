<?php

namespace PhpSPA\Core\Client;

/**
 * HttpClient interface
 * 
 * Defines the contract for HTTP client implementations.
 * 
 * @package Client
 */
interface HttpClient {
   /**
    * Execute an HTTP request.
    *
    * @param string $url The request URL
    * @param string $method The HTTP method
    * @param array $headers The request headers
    * @param string|null $body The request body
    * @param array $options Additional options for the request
    * 
    * @return ClientResponse The HTTP response
    */
   public function request(string $url, string $method, array $headers, ?string $body = null, array $options = []): ClientResponse;

   /**
    * Check if the client is available.
    *
    * @return bool True if the client can be used
    */
   public static function isAvailable(): bool;
}
