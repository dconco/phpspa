<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\Routes\Exceptions\InvalidTypesException;

final class InvalidParameterTypesTest extends TestCase
{
   public function testCatchInvalidParameterTypesBuildsMessage(): void
   {
      $exception = InvalidTypesException::catchInvalidParameterTypes(['INT', 'STRING'], 'BOOL');

      $this->assertInstanceOf(InvalidTypesException::class, $exception);
      $this->assertSame('Invalid request parameter type. {INT, STRING} requested, but got {BOOL}', $exception->getMessage());
   }
}
