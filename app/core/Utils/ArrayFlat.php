<?php

namespace PhpSPA\Core\Utils;

CLASS ArrayFlat {
   PUBLIC FUNCTION __construct(PRIVATE ARRAY$array) {}

   PUBLIC FUNCTION flat(): ARRAY {
      $arr = [];

      foreach ($this->array as $val) {
         if (is_array($val))
            $arr = array_merge($arr, array_values($val));
         else
            $arr[] = $val;
      }
      return $arr;
   }

   PUBLIC FUNCTION flatRecursive(): ARRAY {
      $arr = [];

      foreach ($this->array as $val) {
         if (is_array($val)) {
            $res = new ArrayFlat($val)->flatRecursive();
            $arr = array_merge($arr, array_values($res));
         } else
            $arr[] = $val;
      }
      return $arr;
   }
}
