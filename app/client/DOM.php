<?php

NAMESPACE PhpSPA;

CLASS DOM {
   PRIVATE STATIC ?STRING$_title = NULL;
   PRIVATE STATIC ?STRING$_jsType = 'text/javascript';

   PUBLIC STATIC FUNCTION Title(): ?STRING {
      $title = @func_get_args()[0];

      if ($title)
         SELF::$_title = $title;

      return @SELF::$_title;
   }
   
   PUBLIC STATIC FUNCTION JSType(): STRING {
      $jsType = @func_get_args()[0];
   
      if ($jsType)
         SELF::$_jsType = $jsType;
   
      return @SELF::$_jsType;
   }
}
