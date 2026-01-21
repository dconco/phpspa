<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\ComponentParser;

final class ComponentParserTest extends TestCase
{
   private function parseAttributes(string $attributes): array
   {
      return ComponentParserProxy::parse($attributes);
   }

   public function testParseAttributesToArrayHandlesPlainValues(): void
   {
      $parsed = $this->parseAttributes('name="PhpSPA" version="2.0"');

      $this->assertSame('PhpSPA', $parsed['name']);
      $this->assertSame('2.0', $parsed['version']);
   }

   public function testParseAttributesToArrayDecodesSerializedValues(): void
   {
      $payload = ['a' => 1, 'b' => 'two'];
      $encoded = base64_encode(serialize($payload));

      $parsed = $this->parseAttributes("data=\"{$encoded}\"");

      $this->assertSame($payload, $parsed['data']);
   }
}

final class ComponentParserProxy
{
   use ComponentParser;

   public static function parse(string $attributes): array
   {
      return self::parseAttributesToArray($attributes);
   }
}
