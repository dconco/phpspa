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
   public static function create(string $url): HttpClient
   {
      if (self::$instance !== null) {
         return self::$instance;
      }

      if (!self::isLocalhost($url) && CurlHttpClient::isAvailable()) {
         self::$instance = new CurlHttpClient();
      } else {
         self::$instance = new StreamHttpClient();
      }


      return self::$instance;
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
