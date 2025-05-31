<?php

namespace PhpSPA\Exceptions;

if (class_exists('\PhpSlides\Exception'))
{
   class AppException extends \PhpSlides\Exception
   {

   }
}
else
{
   class AppException extends \Exception
   {

   }
}