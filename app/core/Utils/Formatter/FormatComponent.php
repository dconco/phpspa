<?php

namespace PhpSPA\Core\Utils\Formatter;

use ArrayAccess;
use PhpSPA\Core\Utils\Validate;

class FormatComponent implements ArrayAccess {

   public function __construct(private $data) {
      $this->data = Validate::validate($data);
   }
   
   public function __toString(): string {
      return base64_encode(serialize($this->data));
   }

   public function offsetExists($offset): bool
   {
      return isset($this->data[$offset]);
   }

   public function offsetGet($offset): mixed
   {
      return $this->data[$offset] ?? null;
   }

   public function offsetSet($offset, $value): void
   {
      $this->data[$offset] = $value;
   }

   public function offsetUnset($offset): void
   {
      unset($this->data[$offset]);
   }

   public function __set($name, $value): void
   {
      $this->data[$name] = $value;
   }

   public function __get($name): mixed
   {
      return $this->data[$name] ?? null;
   }

   public function __isset($name): bool
   {
      return isset($this->data[$name]);
   }

   public function __unset($name): void
   {
      unset($this->data[$name]);
   }

   public function __invoke(): mixed
   {
      return base64_encode(serialize(($this->data)(\func_get_args())));
   }
}