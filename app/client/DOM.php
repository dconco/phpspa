<?php

namespace PhpSPA;

class DOM {
   private static ?string $_title = null;
   private static ?array $_currentRoutes = [];
   private static ?array $_currentComponents = [];

   private static array $_meta = [];
   private static array $_links = [];

   /**
    * Set or get link tags dynamically.
    * Signature matches App::link() and Component::link().
    * 
    * When called inside a component, these links merge with and override:
    * - Global App::link() declarations
    * - Component::link() declarations
    * 
    * Links with matching name, href, or rel+type combinations will be overridden.
    *
    * @param callable|string|null $content The callable that returns the CSS/link tag, or direct path/URL
    * @param string|null $name Optional name for the link asset (for overriding)
    * @param string|null $type The type attribute (e.g., 'text/css')
    * @param string|null $rel The relationship attribute (e.g., 'stylesheet', 'preload')
    * @param array $attributes Optional additional attributes as key => value pairs
    * @return array Returns all dynamically set links
    * @since v2.0.8
    * @see https://phpspa.tech/references/dom-utilities/#domlink
    */
   public static function link(
      callable|string|null $content = null,
      ?string $name = null,
      ?string $type = null,
      ?string $rel = 'stylesheet',
      array $attributes = []
   ): array {
      // If called with no arguments, return all links
      if ($content === null) {
         return array_values(self::$_links);
      }

      $entry = [
         'content' => $content,
         'name' => $name,
         'type' => $type,
         'rel' => $rel,
         'attributes' => $attributes
      ];

      // Extract href if content is a string (direct path/URL)
      $href = \is_string($content) ? $content : null;

      // Replace any previous link with the same name, or same href+rel (override in place)
      foreach (self::$_links as $k => $link) {
         // Override by name (preserves position)
         if ($name !== null && isset($link['name']) && $link['name'] === $name) {
            self::$_links[$k] = $entry;
            return array_values(self::$_links);
         }

         // Override by href only when rel matches (prevents cross-rel overrides)
         if ($href !== null && isset($link['content']) && \is_string($link['content']) && $link['content'] === $href &&
             isset($link['rel']) && $rel !== null && $link['rel'] === $rel) {
            self::$_links[$k] = $entry;
            return array_values(self::$_links);
         }
      }

      // Append if no existing key matched
      self::$_links[] = $entry;

      // Always return all links set so far
      return array_values(self::$_links);
   }

   /**
    * Set or get meta tags dynamically.
    * Signature matches App::meta().
    * If called with arguments, sets meta; if called with no args, returns all set meta.
    *
    * @since v2.0.7
    */
   public static function meta(
      ?string $name = null,
      ?string $content = null,
      ?string $property = null,
      ?string $httpEquiv = null,
      ?string $charset = null,
      array $attributes = []
   ): array {
      $entry = [];

      if ($name !== null) {
         $entry['name'] = $name;
      }
      if ($property !== null) {
         $entry['property'] = $property;
      }
      if ($httpEquiv !== null) {
         $entry['http-equiv'] = $httpEquiv;
      }
      if ($charset !== null) {
         $entry['charset'] = $charset;
      }
      if ($content !== null) {
         $entry['content'] = $content;
      }
      foreach ($attributes as $attribute => $value) {
         if (!\is_string($attribute) || $value === null || $value === '') {
            continue;
         }
         $entry[$attribute] = $value;
      }

      // If any meta fields are set, add to static meta array
      if (!empty($entry)) {
         // Remove any previous meta with the same name/property/charset (override)
         foreach (self::$_meta as $k => $meta) {
            if ((isset($entry['name']) && isset($meta['name']) && $entry['name'] === $meta['name']) ||
                  (isset($entry['property']) && isset($meta['property']) && $entry['property'] === $meta['property']) ||
                  (isset($entry['charset']) && isset($meta['charset']) && $entry['charset'] === $meta['charset'])) {
               unset(self::$_meta[$k]);
            }
         }
         self::$_meta[] = $entry;
      }

      // Always return all meta set so far
      return array_values(self::$_meta);
   }

   public static function Title(): ?string {
      $title = \func_get_args()[0] ?? null;

      if ($title)
         static::$_title = $title;

      return static::$_title;
   }

   public static function CurrentRoutes(): array {
      $currentRoute = \func_get_args()[0] ?? null;

      if ($currentRoute)
         static::$_currentRoutes[] = $currentRoute;

      return static::$_currentRoutes;
   }

   public static function CurrentComponents(): array {
      $currentComponent = \func_get_args()[0] ?? null;

      if ($currentComponent)
         static::$_currentComponents[] = $currentComponent;

      return static::$_currentComponents;
   }
}
