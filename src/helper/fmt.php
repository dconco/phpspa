<?php

use PhpSPA\Core\Utils\Validate;

/**
 * Formats data for use in component props.
 * 
 * This function prepares and formats various data types to be safely passed
 * as properties to components, ensuring proper serialization and type handling.
 *
 * @param mixed $data The data to be formatted. Can be of any type (string, array, object, etc.)
 */
function fmt(&...$data): void {
   foreach ($data as &$value) {
      $value = new class ($value) implements ArrayAccess {
         use Validate;

         private $newData;

         public function __construct($newData) {
            $this->newData = $this->validate($newData);
         }
         
         public function __toString(): string {
            return base64_encode(serialize($this->newData));
         }

         public function offsetExists($offset): bool
         {
            return isset($this->newData[$offset]);
         }

         public function offsetGet($offset): mixed
         {
            return $this->newData[$offset] ?? null;
         }

         public function offsetSet($offset, $value): void
         {
            $this->newData[$offset] = $value;
         }

         public function offsetUnset($offset): void
         {
            unset($this->newData[$offset]);
         }

         public function __set($name, $value): void
         {
            $this->newData[$name] = $value;
         }

         public function __get($name): mixed
         {
            return $this->newData[$name] ?? null;
         }

         public function __isset($name): bool
         {
            return isset($this->newData[$name]);
         }

         public function __unset($name): void
         {
            unset($this->newData[$name]);
         }

         public function __invoke(): mixed
         {
            return base64_encode(serialize(($this->newData)(func_get_args())));
         }
      };
   }
}