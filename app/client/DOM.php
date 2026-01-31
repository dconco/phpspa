<?php

namespace PhpSPA;

class DOM {
   private static ?string $_title = null;
   private static ?array $_currentRoutes = [];
   private static ?array $_currentComponents = [];

   private static array $_meta = [];

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
