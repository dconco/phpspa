<?php

namespace PhpSPA\Core\Client;

use function is_array;
use function count;

class ClientResponse {
   /**
    * @param string|false $body The raw response body.
    * @param int $statusCode The HTTP status code (e.g., 200, 404).
    * @param array $rawHeaders The raw response headers.
    * @param string|null $error The error message if request failed.
    */
   public function __construct (
      private readonly string|false $body,
      private readonly int $statusCode,
      private readonly array $rawHeaders,
      private readonly ?string $error = null,
   ) {}

   /**
    * Gets the response body as a JSON-decoded array.
    */
   public function json (): ?array
   {
      if ($this->body === false) return null;
      return json_decode($this->body, true);
   }

   /**
    * Gets the response body as a raw string.
    */
   public function text (): string|false
   {
      return $this->body;
   }

   /**
    * Gets the HTTP status code.
    */
   public function status (): int
   {
      return $this->statusCode;
   }

   /**
    * Checks if the request was successful (2xx status).
    */
   public function ok (): bool
   {
      return $this->statusCode >= 200 && $this->statusCode < 300;
   }

   /**
    * Checks if the request failed.
    */
   public function failed(): bool
   {
      return !$this->ok() || $this->body === false || $this->error !== null;
   }

   /**
    * Gets the error message if request failed.
    */
   public function error(): ?string
   {
      return $this->error;
   }

   /**
     * Gets an associative array of the response headers.
     */
   public function headers(): array
   {
      $headers = [];
      foreach ($this->rawHeaders as $headerLine) {
         // Skip the first line (HTTP status line)
         if (str_starts_with($headerLine, 'HTTP/')) {
            continue;
         }

         // Split on the first colon
         $parts = explode(':', $headerLine, 2);
         if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $headers[$key] = $value;
         }
      }
      return $headers;
   }
   
   public function __isset($name): bool
   {
      $response = $this->json();

      if (!is_array($response)) {
         return false;
      }
      return isset($response[$name]);
   }

   public function __get($name): mixed
   {
      $response = $this->json();

      if (!is_array($response)) {
         return null;
      }
      return $response[$name] ?? null;
   }
}