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
   public function request(string $url, string $method, array $headers, ?string $body = null, array $options = []): ClientResponse
   {
      // Increase PHP max execution time if timeout is higher
      $timeout = $options['timeout'] ?? 30;
      if ($timeout > ini_get('max_execution_time')) {
         @set_time_limit((int)$timeout + 10);
      }

      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['follow_redirects'] ?? true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, $options['max_redirects'] ?? 10);
      curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4 for localhost issues
      
      // Handle timeout - support both seconds (int/float) and milliseconds
      $timeout = $options['timeout'] ?? 30;
      if ($timeout > 0 && $timeout < 1) {
         // Use milliseconds for sub-second timeouts
         curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int)($timeout * 1000));
      } else {
         // Use seconds for timeouts >= 1
         curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
      }
      
      $connectTimeout = $options['connect_timeout'] ?? 10;
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $connectTimeout);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify_ssl'] ?? false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($options['verify_ssl'] ?? false) ? 2 : 0);
      
      if (isset($options['cert_path'])) {
         curl_setopt($ch, CURLOPT_CAINFO, $options['cert_path']);
      }
      
      if (isset($options['user_agent'])) {
         curl_setopt($ch, CURLOPT_USERAGENT, $options['user_agent']);
      }
      
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
      $error = curl_error($ch);
      $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      curl_close($ch);

      if ($response === false || $error) {
         return new ClientResponse(false, 0, [], $error ?: 'Request failed');
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
