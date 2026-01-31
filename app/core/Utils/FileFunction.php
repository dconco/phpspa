<?php

namespace PhpSPA\Core\Utils;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class FileFunction
{
   private ReflectionFunctionAbstract $function;

   public function __construct(callable|string|array $function) {
      $this->function = \is_array($function) ? new ReflectionMethod($function[0], $function[1]) : new ReflectionFunction($function);
   }

   /**
    * Get the file modification time of a file
    *
    * @return int The file modification time as a Unix timestamp
    */
   public function getFileModificationTime(): int
   {
      return filemtime($this->getFileName()) ?: 0;
   }

   public function getFunction(): ReflectionFunctionAbstract
   {
      return $this->function;
   }

   public function getFileName(): string|false
   {
      return $this->function->getFileName();
   }
}
