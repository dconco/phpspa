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
 * @package PhpSPA\Http\Security
 * @category Security
 * @author GitHub Copilot
 * @version 1.0.0
 */
class Nonce {
   private static $enabled = false;   // default off

   private static $nonce = null;

   private static $directives = [
      'default-src' => ["'self'"],
      'script-src'  => ["'self'"],
      'style-src'   => ["'self'"],
      'object-src'  => ["'none'"],
      'base-uri'    => ["'self'"],
      'font-src'    => ["'self'"],
      'img-src'     => ["'self'", "data:"],
    ];
   
   /**
    * Enable nonce-based CSP and optionally override allowed sources.
    * Example:
    * Nonce::enable([
    *   'script-src' => ["'self'", "https://cdn.jsdelivr.net"],
    *   'style-src'  => ["'self'", "https://fonts.googleapis.com"],
    *   'font-src'   => ["https://fonts.gstatic.com"]
    * ]);
    */
   public static function enable(array $sources = []): void {
      self::$enabled = true;

      // Merge custom sources with defaults
      foreach ($sources as $directive => $values) {
         self::$directives[$directive] = $values;
      }

      self::sendHeader();
   }

   /**
    * Disable nonce (CSP will not inject nonces).
    */
   public static function disable(): void {
      self::$enabled = false;
      self::$nonce = null;
   }

   /**
    * Get the nonce (if enabled).
    */
   public static function nonce(): ?string {
      if (!self::$enabled) return null;
      if (self::$nonce === null) self::$nonce = base64_encode(random_bytes(16));

      return self::$nonce;
   }

   private static function sendHeader(): void {
      if (!self::$enabled) return;

      $nonce = self::nonce();

      // Ensure script-src and style-src get the nonce automatically
      if (isset(self::$directives['script-src'])) {
         self::$directives['script-src'][] = "'nonce-$nonce'";
      }
      if (isset(self::$directives['style-src'])) {
         self::$directives['style-src'][] = "'nonce-$nonce'";
      }

      // Build CSP string
      $parts = [];
      foreach (self::$directives as $dir => $values) {
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
   public static function attr(): string {
      return self::$enabled && self::$nonce !== null
         ? 'nonce="' . self::$nonce . '"'
         : '';
   }

}
