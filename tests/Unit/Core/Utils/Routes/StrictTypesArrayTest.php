<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StrictTypesArrayTest extends TestCase
{
   public function testMatchStrictTypeCastsArrayValues(): void
   {
      $json = '[1,"two"]';
      $result = StrictTypesArrayProxy::matchStrictTypePublic($json, ['ARRAY<INT, STRING>']);

      $this->assertSame([1, 'two'], $result);
   }
}

final class StrictTypesArrayProxy
{
   use PhpSPA\Core\Utils\Routes\StrictTypes;

   public static function matchStrictTypePublic(string $needle, array $haystack): int|bool|float|array|string
   {
      return self::matchStrictType($needle, $haystack);
   }
}
