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
    * Extract raw cURL options from the mixed PhpSPA options array.
    *
    * Supports:
    * - $options['curl'] or $options['curl_options'] as array<int,mixed>
    * - top-level int keys (CURLOPT_* constants)
    * - top-level string keys like 'CURLOPT_PROXY'
    *
    * @param array $options
    * @return array<int, mixed>
    */
   private function extractCurlOptions(array $options): array
   {
      $curlOptions = [];

      foreach ($options as $key => $value) {
         if (\is_int($key)) {
            $curlOptions[$key] = $value;
         }
         else if (\is_string($key) && str_starts_with($key, 'CURLOPT_') && \defined($key)) {
            $curlOptions[\constant($key)] = $value;
         }
      }

      $nested = $options['curl'] ?? ($options['curl_options'] ?? null);
      if (\is_array($nested)) {
         foreach ($nested as $key => $value) {
            if (\is_string($key) && str_starts_with($key, 'CURLOPT_') && \defined($key)) {
               $key = \constant($key);
            }
            if (\is_int($key)) {
               $curlOptions[$key] = $value;
            }
         }
      }

      return $curlOptions;
   }

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
      if (isset($options['ip_resolve'])) {
         $ipResolve = $options['ip_resolve'];
         if ($ipResolve === 'v4') {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
         } elseif ($ipResolve === 'v6') {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
         }
      }
      
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

      // --- Handle Unix Socket ---
      if (isset($options['unix_socket_path'])) {
         curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $options['unix_socket_path']);
      }

      if (isset($options['cert_path'])) {
         curl_setopt($ch, CURLOPT_CAINFO, $options['cert_path']);
      }
      
      if (isset($options['user_agent'])) {
         curl_setopt($ch, CURLOPT_USERAGENT, $options['user_agent']);
      }
      
      // Build headers array for cURL
      $curlHeaders = [];
      foreach ($headers as $key => $value) {
         if (\is_array($value)) $value = implode(', ', $value);
         
         $curlHeaders[] = "$key: $value";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
      
      // Add body for POST, PUT, PATCH
      if ($body !== null) {
         curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      }

      // Apply any user-provided raw cURL options (advanced)
      $curlOptions = $this->extractCurlOptions($options);
      if (!empty($curlOptions)) {
         @curl_setopt_array($ch, $curlOptions);
      }

      $response = curl_exec($ch);
      $errorNo = curl_errno($ch);
      $error = curl_error($ch);
      $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      unset($ch);

      if ($response === false || $errorNo !== 0) {
         $message = $error ?: 'Request failed';
         if ($errorNo !== 0) {
            $message = "cURL error ($errorNo): $message";
         }
         return new ClientResponse(false, 0, [], $message);
      }

      $headerString = substr($response, 0, $headerSize);
      $responseBody = substr($response, $headerSize);
      $responseHeaders = array_filter(explode("\r\n", $headerString));

      return new ClientResponse($responseBody, $statusCode, $responseHeaders);
   }

   /**
    * Prepare an asynchronous cURL request without executing it.
    * Returns a cURL handle that can be executed later with curl_exec().
    *
    * @param string $url The URL to request
    * @param string $method The HTTP method
    * @param array $headers Headers to send
    * @param string|null $body Request body
    * @param array $options Additional options
    * @return \CurlHandle The prepared cURL handle
    */
   public function prepareAsync(string $url, string $method, array $headers, ?string $body = null, array $options = []): \CurlHandle
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
      if (isset($options['ip_resolve'])) {
         $ipResolve = $options['ip_resolve'];
         if ($ipResolve === 'v4') {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
         } elseif ($ipResolve === 'v6') {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
         }
      }
      
      // Handle timeout - support both seconds (int/float) and milliseconds
      $timeout = $options['timeout'] ?? 30;
      if ($timeout > 0 && $timeout < 1) {
         // Use milliseconds for sub-second timeouts
         curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int)($timeout * 1000));
      } else {
         // Use seconds for timeouts >= 1
         curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
      }

      $connectTimeout = $options['connect_timeout'] ?? 10;
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $connectTimeout);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify_ssl'] ?? true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($options['verify_ssl'] ?? true) ? 2 : 0);

      // --- Handle Unix Socket ---
      if (isset($options['unix_socket_path'])) {
         curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $options['unix_socket_path']);
      }

      if (isset($options['cert_path'])) {
         curl_setopt($ch, CURLOPT_CAINFO, $options['cert_path']);
      }

      if (isset($options['user_agent'])) {
         curl_setopt($ch, CURLOPT_USERAGENT, $options['user_agent']);
      }

      // Build headers array for cURL
      $curlHeaders = [];
      foreach ($headers as $key => $value) {
         if (\is_array($value)) $value = implode(', ', $value);

         $curlHeaders[] = "$key: $value";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
      
      // Add body for POST, PUT, PATCH
      if ($body !== null) {
         curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      }

      // Apply any user-provided raw cURL options (advanced)
      $curlOptions = $this->extractCurlOptions($options);
      if (!empty($curlOptions)) {
         @curl_setopt_array($ch, $curlOptions);
      }
      
      // Return the prepared handle without executing
      return $ch;
   }

   /**
    * {@inheritdoc}
    */
   public static function isAvailable(): bool
   {
      return function_exists('curl_init');
   }
}
