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
   public function request(string $url, string $method, array $headers, ?string $body = null): ClientResponse
   {
      $options = [
         'http' => [
            'method' => $method,
            'header' => $this->buildHeaderString($headers),
            'ignore_errors' => true,
         ]
      ];

      if ($body !== null) {
         $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
         $headers['Content-Length'] = strlen($body);
         
         $options['http']['content'] = $body;
         $options['http']['header'] = $this->buildHeaderString($headers);
      }

      $context = stream_context_create($options);
      $responseBody = @file_get_contents($url, false, $context);

      $responseHeaders = $http_response_header ?? [];

      $statusCode = 0;
      if (isset($responseHeaders[0])) {
         @list( , $statusCode, ) = explode(' ', $responseHeaders[0], 3);
      }

      return new ClientResponse($responseBody, (int) $statusCode, $responseHeaders);
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
         $lines[] = "$key: $value";
      }
      return implode("\r\n", $lines);
   }
}
