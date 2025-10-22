<?php

namespace PhpSPA\Core\Client;

/**
 * AsyncPool
 * 
 * Manages multiple concurrent async requests using cURL multi-handle.
 * Allows true parallel execution of HTTP requests.
 * 
 * @package Client
 */
class AsyncPool {
   /** @var AsyncResponse[] */
   private array $promises = [];
   
   /** @var \CurlHandle[] */
   private array $handles = [];

   /**
    * Add an async request to the pool.
    *
    * @param AsyncResponse $promise The async response to add
    * @return self
    */
   public function add(AsyncResponse $promise): self
   {
      $this->promises[] = $promise;
      $this->handles[] = $promise->getHandle();
      return $this;
   }

   /**
    * Execute all requests in parallel and wait for all to complete.
    *
    * @return ClientResponse[] Array of responses in the same order as promises
    */
   public function wait(): array
   {
      if (empty($this->handles)) {
         return [];
      }

      // Create multi handle
      $multiHandle = curl_multi_init();

      // Add all handles to the multi handle
      foreach ($this->handles as $handle) {
         curl_multi_add_handle($multiHandle, $handle);
      }

      // Execute all requests in parallel
      $running = null;
      do {
         curl_multi_exec($multiHandle, $running);
         curl_multi_select($multiHandle);
      } while ($running > 0);

      // Collect responses
      $responses = [];
      foreach ($this->promises as $promise) {
         $responses[] = $promise->wait();
      }

      // Clean up
      foreach ($this->handles as $handle) {
         curl_multi_remove_handle($multiHandle, $handle);
      }
      curl_multi_close($multiHandle);

      return $responses;
   }

   /**
    * Execute all requests and return associative array with keys.
    *
    * @param array $keys Array of keys to use for the responses
    * @return array Associative array of responses
    */
   public function waitWithKeys(array $keys): array
   {
      $responses = $this->wait();
      return array_combine($keys, $responses);
   }

   /**
    * Execute all requests and call a callback for each response.
    *
    * @param callable $callback Function to call for each response
    * @return self
    */
   public function each(callable $callback): self
   {
      $responses = $this->wait();
      foreach ($responses as $index => $response) {
         $callback($response, $index);
      }
      return $this;
   }

   /**
    * Get the number of promises in the pool.
    *
    * @return int
    */
   public function count(): int
   {
      return count($this->promises);
   }
}
