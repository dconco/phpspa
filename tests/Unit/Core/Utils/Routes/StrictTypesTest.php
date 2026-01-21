<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StrictTypesTest extends TestCase
{
   public function testMatchTypeRecognizesInt(): void
   {
      $this->assertTrue(StrictTypesProxy::matchTypePublic('123', ['INT']));
   }

   public function testMatchStrictTypeCastsBoolean(): void
   {
      $this->assertTrue(StrictTypesProxy::matchStrictTypePublic('true', ['BOOL']));
   }

   public function testMatchStrictTypeCastsFloat(): void
   {
      $this->assertSame(1.5, StrictTypesProxy::matchStrictTypePublic('1.5', ['FLOAT']));
   }
}

final class StrictTypesProxy
{
   use PhpSPA\Core\Utils\Routes\StrictTypes;

   public static function matchTypePublic(string $needle, array $haystack): bool
   {
      return self::matchType($needle, $haystack);
   }

   public static function matchStrictTypePublic(string $needle, array $haystack): int|bool|float|array|string
   {
      return self::matchStrictType($needle, $haystack);
   }
}
