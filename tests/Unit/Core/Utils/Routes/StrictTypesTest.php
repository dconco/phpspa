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

   // ==================== ENUM<...> ====================

   public function testEnumMatchesListedStringLiteral(): void
   {
      $this->assertTrue(StrictTypesProxy::matchTypePublic('dave', ['ENUM<5, 20, dave>']));
   }

   public function testEnumMatchesListedNumericLiteral(): void
   {
      $this->assertTrue(StrictTypesProxy::matchTypePublic('5', ['ENUM<5, 20, dave>']));
      $this->assertTrue(StrictTypesProxy::matchTypePublic('20', ['ENUM<5, 20, dave>']));
   }

   public function testEnumRejectsValueNotInList(): void
   {
      $this->assertFalse(StrictTypesProxy::matchTypePublic('john', ['ENUM<5, 20, dave>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('6', ['ENUM<5, 20, dave>']));
   }

   public function testEnumPreservesLiteralCase(): void
   {
      // Case-sensitive app: router does NOT lowercase, so literal case is kept.
      // 'Dave' must match ENUM<Dave>, but 'dave' must not.
      $this->assertTrue(StrictTypesProxy::matchTypePublic('Dave', ['ENUM<Dave>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('dave', ['ENUM<Dave>']));
   }

   public function testEnumCaseInsensitiveFlowMatches(): void
   {
      // Case-insensitive app: router lowercases both route literals and the URI
      // upstream, so by the time it reaches here both are lowercase and match.
      $this->assertTrue(StrictTypesProxy::matchTypePublic('dave', ['ENUM<dave>']));
   }

   public function testEnumComposesWithUnion(): void
   {
      // {x: int|enum<a,b>} -> realMatch splits on | into ['INT', 'enum<a,b>'].
      // An int value matches the INT arm; a listed literal matches the ENUM arm.
      $this->assertTrue(StrictTypesProxy::matchTypePublic('42', ['INT', 'ENUM<a, b>']));
      $this->assertTrue(StrictTypesProxy::matchTypePublic('a', ['INT', 'ENUM<a, b>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('c', ['INT', 'ENUM<a, b>']));
   }

   public function testEnumStrictTypeReturnsValue(): void
   {
      // matchStrictType returns the cast value; a listed numeric casts to int,
      // a listed string stays a string.
      $this->assertSame(5, StrictTypesProxy::matchStrictTypePublic('5', ['ENUM<5, 20, dave>']));
      $this->assertSame('dave', StrictTypesProxy::matchStrictTypePublic('dave', ['ENUM<5, 20, dave>']));
   }

   public function testEnumStrictTypeRejectsUnlisted(): void
   {
      $this->assertFalse(StrictTypesProxy::matchStrictTypePublic('john', ['ENUM<5, 20, dave>']));
   }

   // ==================== INT<MIN> / INT<MIN,MAX> ====================

   public function testIntMinOnlyAcceptsEqualAndAbove(): void
   {
      // INT<5> -> value must be >= 5
      $this->assertTrue(StrictTypesProxy::matchTypePublic('5', ['INT<5>']));   // equal to min
      $this->assertTrue(StrictTypesProxy::matchTypePublic('10', ['INT<5>']));  // above min
      $this->assertTrue(StrictTypesProxy::matchTypePublic('999', ['INT<5>'])); // no upper bound
   }

   public function testIntMinOnlyRejectsBelow(): void
   {
      // INT<5> -> anything < 5 fails
      $this->assertFalse(StrictTypesProxy::matchTypePublic('4', ['INT<5>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('0', ['INT<5>']));
   }

   public function testIntExactRangeMatchesOnlyThatValue(): void
   {
      // INT<5,5> -> only 5, nothing else
      $this->assertTrue(StrictTypesProxy::matchTypePublic('5', ['INT<5,5>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('4', ['INT<5,5>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('6', ['INT<5,5>']));
   }

   public function testIntRangeInclusiveBounds(): void
   {
      // INT<1,10> -> [1, 10] inclusive
      $this->assertTrue(StrictTypesProxy::matchTypePublic('1', ['INT<1,10>']));
      $this->assertTrue(StrictTypesProxy::matchTypePublic('10', ['INT<1,10>']));
      $this->assertTrue(StrictTypesProxy::matchTypePublic('7', ['INT<1,10>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('0', ['INT<1,10>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('11', ['INT<1,10>']));
   }

   public function testIntRangeWithSpaceAfterComma(): void
   {
      // INT<1, 10> -> whitespace after comma tolerated by the regex
      $this->assertTrue(StrictTypesProxy::matchTypePublic('5', ['INT<1, 10>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('20', ['INT<1, 10>']));
   }

   public function testIntRangeWithZeroMaxIsNotTreatedAsNoMax(): void
   {
      // Regression: old code did `(int)$matches[2] ?? null`, making a real max of 0
      // indistinguishable from "no max". INT<0,0> must accept only 0.
      $this->assertTrue(StrictTypesProxy::matchTypePublic('0', ['INT<0,0>']));
      $this->assertFalse(StrictTypesProxy::matchTypePublic('1', ['INT<0,0>']));
   }

   public function testIntRangeStrictTypeCastsToInt(): void
   {
      // matchStrictType returns the cast int value for a passing range.
      $this->assertSame(5, StrictTypesProxy::matchStrictTypePublic('5', ['INT<5>']));
      $this->assertSame(7, StrictTypesProxy::matchStrictTypePublic('7', ['INT<1,10>']));
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
