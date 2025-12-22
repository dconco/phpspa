<?php

namespace PhpSPA\Core\Client;

/**
 * HttpClientFactory
 * 
 * Factory for creating HTTP client instances.
 * Automatically selects the best available client implementation.
 * 
 * @package Client
 */
class HttpClientFactory {
   private static ?HttpClient $instance = null;

   /**
    * Get an HTTP client instance.
    * 
    * Prefers cURL if available, falls back to streams.
    * For localhost URLs, always uses streams due to cURL compatibility issues on Windows.
    *
    * @return HttpClient
    */
   public static function create(): HttpClient
   {
      if (self::$instance !== null) {
         return self::$instance;
      }

      return self::$instance = CurlHttpClient::isAvailable() ? new CurlHttpClient() : new StreamHttpClient();
   }

   /**
    * Check if URL is localhost
    * 
    * @param string $url
    * @return bool
    */
   private static function isLocalhost(string $url): bool
   {
      $host = parse_url($url, PHP_URL_HOST);
      return \in_array($host, ['localhost', '127.0.0.1', '::1'], true);
   }

   /**
    * Reset the factory instance.
    * 
    * @return void
    */
   public static function reset(): void
   {
      self::$instance = null;
   }
}
