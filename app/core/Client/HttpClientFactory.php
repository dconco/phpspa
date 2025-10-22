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
    *
    * @return HttpClient
    */
   public static function create(): HttpClient
   {
      if (self::$instance !== null) {
         return self::$instance;
      }

      if (CurlHttpClient::isAvailable()) {
         self::$instance = new CurlHttpClient();
      } else {
         self::$instance = new StreamHttpClient();
      }

      return self::$instance;
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
