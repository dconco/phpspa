<?php

namespace PhpSPA\Core\Client;

/**
 * StreamHttpClient
 * 
 * HTTP client implementation using PHP streams (file_get_contents).
 * Fallback when cURL is not available.
 * 
 * @package Client
 */
class StreamHttpClient implements HttpClient {
   /**
    * {@inheritdoc}
    */
   public function request(string $url, string $method, array $headers, ?string $body = null, array $options = []): ClientResponse
   {
      $httpOptions = [
         'method' => $method,
         'header' => $this->buildHeaderString($headers),
         'ignore_errors' => true,
         'timeout' => $options['timeout'] ?? 30,
      ];

      if (isset($options['user_agent'])) {
         $httpOptions['user_agent'] = $options['user_agent'];
      }

      if ($body !== null) {
         $httpOptions['content'] = $body;
      }

      $contextOptions = ['http' => $httpOptions];

      if (isset($options['verify_ssl']) && !$options['verify_ssl']) {
         $contextOptions['ssl'] = [
            'verify_peer' => false,
            'verify_peer_name' => false,
         ];
      }

      if (isset($options['cert_path'])) {
         $contextOptions['ssl'] ??= [];
         $contextOptions['ssl']['cafile'] = $options['cert_path'];
      }

      $context = stream_context_create($contextOptions);
      $responseBody = @file_get_contents($url, false, $context);


      if (function_exists('http_get_last_response_headers')) {
         $http_response_header = http_get_last_response_headers();
      }
      $responseHeaders = @$http_response_header ?? [];

      $statusCode = 0;
      $error = null;

      if (isset($responseHeaders[0])) {
         @[ , $statusCode, ] = explode(' ', $responseHeaders[0], 3);
      }

      if ($responseBody === false) {
         $error = error_get_last()['message'] ?? 'Request failed';
      }

      return new ClientResponse($responseBody, (int) $statusCode, $responseHeaders, $error);
   }

   /**
    * {@inheritdoc}
    */
   public static function isAvailable(): bool
   {
      return true; // Always available as fallback
   }

   /**
    * Builds a formatted header string from an array of headers.
    *
    * @param array $headers An associative array of header names and values
    * @return string A formatted header string with each header on a new line
    */
   private function buildHeaderString(array $headers): string
   {
      $lines = [];
      foreach ($headers as $key => $value) {
         if (\is_array($value)) $value = implode(', ', $value);

         $lines[] = "$key: $value";
      }
      return implode("\r\n", $lines);
   }
}
