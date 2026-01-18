<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\Validate;

final class ValidateTest extends TestCase
{
   public function testValidateReturnsSameString(): void
   {
      $input = '<b>PhpSPA</b>';

      $this->assertSame($input, Validate::validate($input));
   }

   public function testValidateReturnsSameArrayValues(): void
   {
      $payload = ['name' => '<i>PhpSPA</i>', 'count' => 2];

      $this->assertSame($payload, Validate::validate($payload));
   }
}
