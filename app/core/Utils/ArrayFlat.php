<?php

namespace PhpSPA\Core\Utils;

class ArrayFlat {

   public function __construct(private array $array) {}

   /**
    * Flattens a multi-dimensional array into a single-dimensional array.
    *
    * @return array The flattened array.
    */
   public function flat(): array {
      $arr = [];

      foreach ($this->array as $val) {
         if (\is_array($val))
            $arr = [...$arr, ...array_values($val)];
         else
            $arr[] = $val;
      }
      return $arr;
   }

   /**
    * Recursively flattens a multi-dimensional array into a single-dimensional array.
    *
    * @return array The flattened array.
    */
   public function flatRecursive(): array {
      $arr = [];

      foreach ($this->array as $val) {
         if (\is_array($val)) {
            $flat = new ArrayFlat($val);
            $res = $flat->flatRecursive();
            $arr = [...$arr, ...array_values($res)];
         } else
            $arr[] = $val;
      }
      return $arr;
   }

}
