<?php

namespace PhpSPA;

class DOM {
   private static ?string $_title = null;
   private static ?array $_currentRoutes = [];
   private static ?array $_currentComponents = [];
   private static ?string $_jsType = 'text/javascript';

   public static function Title(): ?string {
      $title = func_get_args()[0] ?? null;

      if ($title)
         static::$_title = $title;

      return static::$_title;
   }

   public static function JSType(): string {
      $jsType = func_get_args()[0] ?? null;
   
      if ($jsType)
         static::$_jsType = $jsType;
   
      return static::$_jsType;
   }

   public static function CurrentRoutes(): array {
      $currentRoute = func_get_args()[0] ?? null;

      if ($currentRoute)
         static::$_currentRoutes[] = $currentRoute;

      return static::$_currentRoutes;
   }

   public static function CurrentComponents(): array {
      $currentComponent = func_get_args()[0] ?? null;

      if ($currentComponent)
         static::$_currentComponents[] = $currentComponent;

      return static::$_currentComponents;
   }
}
