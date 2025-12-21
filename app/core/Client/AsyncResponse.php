<?php

namespace PhpSPA\Core\Client;

/**
 * AsyncResponse
 * 
 * Represents an asynchronous HTTP response that can be resolved later.
 * 
 * @package Client
 */
class AsyncResponse {
   private \CurlHandle $curlHandle;
   private ?ClientResponse $response = null;
   private bool $resolved = false;

   /**
    * @param \CurlHandle $curlHandle The cURL handle for the async request
    */
   public function __construct(\CurlHandle $curlHandle)
   {
      $this->curlHandle = $curlHandle;
   }

   /**
    * Wait for the request to complete and get the response.
    *
    * @return ClientResponse
    */
   public function wait(): ClientResponse
   {
      if ($this->resolved) {
         return $this->response;
      }

      $response = curl_exec($this->curlHandle);
      $error = curl_error($this->curlHandle);
      $headerSize = curl_getinfo($this->curlHandle, CURLINFO_HEADER_SIZE);
      $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

      curl_close($this->curlHandle);

      if ($response === false || $error) {
         $this->response = new ClientResponse(false, 0, [], $error ?: 'Request failed');
      } else {
         $headerString = substr($response, 0, $headerSize);
         $responseBody = substr($response, $headerSize);
         $responseHeaders = array_filter(explode("\r\n", $headerString));
         $this->response = new ClientResponse($responseBody, $statusCode, $responseHeaders);
      }

      $this->resolved = true;
      return $this->response;
   }

   /**
    * Execute a callback when the response is ready.
    *
    * @param callable $callback Callback function that receives ClientResponse
    * @return self
    */
   public function then(callable $callback): self
   {
      $response = $this->wait();
      $callback($response);
      return $this;
   }

   /**
    * Get the cURL handle.
    *
    * @return \CurlHandle
    */
   public function getHandle(): \CurlHandle
   {
      return $this->curlHandle;
   }

   /**
    * Execute multiple async requests in parallel using cURL multi-handle.
    *
    * @param AsyncResponse[] $promises Array of AsyncResponse objects
    * @return ClientResponse[] Array of ClientResponse objects in same order
    */
   public static function all(array $promises): array
   {
      if (empty($promises)) {
         return [];
      }

      // Create multi-handle
      $multiHandle = curl_multi_init();
      $handleMap = [];

      // Add all handles to multi-handle
      foreach ($promises as $index => $promise) {
         $handle = $promise->getHandle();
         curl_multi_add_handle($multiHandle, $handle);
         $handleMap[(int)$handle] = ['promise' => $promise, 'index' => $index];
      }

      // Execute all requests in parallel
      $running = null;
      do {
         curl_multi_exec($multiHandle, $running);
         curl_multi_select($multiHandle);
      } while ($running > 0);

      // Collect responses
      $responses = array_fill(0, \count($promises), null);

      foreach ($promises as $index => $promise) {
         $handle = $promise->getHandle();
         
         $response = curl_multi_getcontent($handle);
         $error = curl_error($handle);
         $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
         $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

         if ($response === false || $error) {
            $responses[$index] = new ClientResponse(false, 0, [], $error ?: 'Request failed');
         } else {
            $headerString = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            $responseHeaders = array_filter(explode("\r\n", $headerString));
            $responses[$index] = new ClientResponse($responseBody, $statusCode, $responseHeaders);
         }

         curl_multi_remove_handle($multiHandle, $handle);
         curl_close($handle);
      }

      curl_multi_close($multiHandle);

      return $responses;
   }
}
