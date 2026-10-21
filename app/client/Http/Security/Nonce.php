<?php

namespace PhpSPA\Http\Security;

/**
 * Nonce class for handling cryptographic nonces and security tokens.
 * 
 * This class provides functionality for generating, validating, and managing
 * nonces (number used once) to prevent CSRF attacks and replay attacks in
 * web applications. Nonces are typically used in forms, AJAX requests, and
 * other security-sensitive operations.
 *
 * @package Security
 * @author dconco <me@dconco.tech>
 * @see https://phpspa.tech/security/content-security-policy
 */
class Nonce {
   private static bool $enabled = false;   // default off

   private static ?string $nonce = null;

   private static array $directives = [
      'default-src' => ["'self'"],
      'script-src' => ["'self'"],
      'style-src' => ["'self'"],
      'connect-src' => ["'self'"],
      'object-src' => ["'none'"],
      'base-uri' => ["'self'"],
      'font-src' => ["'self'"],
      'img-src' => ["'self'"],
      'media-src' => ["'self'"],
      'frame-src' => ["'self'"],
      'manifest-src' => ["'self'"],
      'worker-src' => ["'self'"],
      'prefetch-src' => ["'self'"],
      'navigate-to' => ["'self'"],
      'form-action' => ["'self'"],
      'frame-ancestors' => ["'none'"],
   ];

   /**
    * Enable nonce-based CSP and optionally override allowed sources.
    *
    * Example:
    * ```
    * Nonce::enable([
    *   'script-src' => ["https://cdn.jsdelivr.net"],
    *   'style-src'  => ["https://fonts.googleapis.com"],
    *   'font-src'   => ["https://fonts.gstatic.com"]
    * ]);
    * ```
    *
    * @param array{
    *    'default-src': array<string>,
    *    'script-src': array<string>,
    *    'style-src': array<string>,
    *    'object-src': array<string>,
    *    'font-src': array<string>,
    *    'img-src': array<string>,
    *    'media-src': array<string>,
    *    'font-src': array<string>,
    *    'base-uri': array<string>,
    *    'connect-src': array<string>,
    *    'frame-src': array<string>,
    *    'child-src': array<string>,
    *    'form-action': array<string>,
    *    'frame-ancestors': array<string>,
    *    'manifest-src': array<string>,
    *    'worker-src': array<string>,
    *    'prefetch-src': array<string>,
    *    'navigate-to': array<string>,
    * } $sources
    *
    * @see https://phpspa.tech/security/content-security-policy
    */
   public static function enable (array $sources = []): void
   {
      self::$enabled = true;

      // Merge custom sources with defaults (prevent duplicates)
      foreach ($sources as $directive => $values) {
         $existing = self::$directives[$directive] ?? [];
         self::$directives[$directive] = array_unique([...$existing, ...$values]);
      }
   }

   public static function reset (): void
   {
      self::$nonce = null;
   }

   /**
    * Disable nonce (CSP will not inject nonces).
    */
   public static function disable (): void
   {
      self::$enabled = false;
      self::$nonce = null;
   }

   /**
    * Get the nonce (if enabled).
    */
   public static function nonce (): ?string
   {
      if (!self::$enabled) return null;
      if (self::$nonce === null) self::$nonce = bin2hex(random_bytes(16));;

      return self::$nonce;
   }

   public static function sendHeader (): void
   {
      if (!self::$enabled) return;

      $nonce = self::nonce();
      $directives = self::$directives;

      // Ensure script-src and style-src get the nonce automatically
      // Only add nonce if 'unsafe-inline' and 'unsafe-hashes' is not present
      if (isset($directives['script-src'])) {
         $hasUnsafeInline = \in_array("'unsafe-inline'", $directives['script-src']);
         $hasUnsafeHashes = \in_array("'unsafe-hashes'", $directives['script-src']);

         if (!$hasUnsafeInline && !$hasUnsafeHashes) {
            $directives['script-src'][] = "'nonce-$nonce'";
         }
      }

      if (isset($directives['style-src'])) {
         $hasUnsafeInline = \in_array("'unsafe-inline'", $directives['style-src']);
         $hasUnsafeHashes = \in_array("'unsafe-hashes'", $directives['style-src']);

         if (!$hasUnsafeInline && !$hasUnsafeHashes) {
            $directives['style-src'][] = "'nonce-$nonce'";
         }
      }

      // Build CSP string
      $parts = [];
      foreach ($directives as $dir => $values) {
         $parts[] = $dir . " " . implode(" ", array_unique($values));
      }

      $csp = implode("; ", $parts);
      if (!headers_sent()) {
         header("Content-Security-Policy: $csp");
      }
   }

   /**
    * Return the HTML nonce attribute for inline <script>.
    */
   public static function attr (): string
   {
      return self::$enabled && self::$nonce !== null
         ? 'nonce="' . self::$nonce . '"'
         : '';
   }

}
