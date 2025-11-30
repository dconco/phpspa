<?php

NAMESPACE PhpSPA;

CLASS DOM {
   PRIVATE STATIC ?STRING$_title = NULL;

   PUBLIC STATIC FUNCTION Title(): ?STRING {
      $title = @func_get_args()[0];

      if ($title)
         SELF::$_title = $title;

      return @SELF::$_title;
   }
}
