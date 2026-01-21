<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\Routes\Exceptions\InvalidTypesException;

final class InvalidTypesExceptionTest extends TestCase
{
   public function testCatchInvalidStrictTypesThrows(): void
   {
      $this->expectException(InvalidTypesException::class);
      $this->expectExceptionMessage('{BADTYPE} is not recognized as a URL parameter type');

      InvalidTypesException::catchInvalidStrictTypes('BADTYPE');
   }
}
