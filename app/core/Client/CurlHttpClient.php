<?php

namespace PhpSPA\Core\Client;

/**
 * CurlHttpClient
 * 
 * HTTP client implementation using cURL extension.
 * 
 * @package Client
 */
class CurlHttpClient implements HttpClient {
   /**
    * {@inheritdoc}
    */
   public function request(string $url, string $method, array $headers, ?string $body = null): ClientResponse
   {
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

      // Build headers array for cURL
      $curlHeaders = [];
      foreach ($headers as $key => $value) {
         $curlHeaders[] = "$key: $value";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

      // Add body for POST, PUT, PATCH
      if ($body !== null) {
         curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      }

      $response = curl_exec($ch);
      $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      curl_close($ch);

      if ($response === false) {
         return new ClientResponse(false, 0, []);
      }

      $headerString = substr($response, 0, $headerSize);
      $responseBody = substr($response, $headerSize);
      $responseHeaders = array_filter(explode("\r\n", $headerString));

      return new ClientResponse($responseBody, $statusCode, $responseHeaders);
   }

   /**
    * {@inheritdoc}
    */
   public static function isAvailable(): bool
   {
      return function_exists('curl_init');
   }
}
