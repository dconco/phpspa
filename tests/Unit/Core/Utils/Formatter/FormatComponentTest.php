<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\Formatter\FormatComponent;

final class FormatComponentTest extends TestCase
{
   public function testArrayAccessAndMagicAccessors(): void
   {
      $component = new FormatComponent(['name' => 'PhpSPA']);

      $this->assertSame('PhpSPA', $component['name']);
      $this->assertTrue(isset($component['name']));

      $component['version'] = '2.0';
      $this->assertSame('2.0', $component['version']);

      unset($component['name']);
      $this->assertFalse(isset($component['name']));

      $component->title = 'Hello';
      $this->assertSame('Hello', $component->title);
      $this->assertTrue(isset($component->title));

      unset($component->title);
      $this->assertFalse(isset($component->title));
   }

   public function testToStringEncodesData(): void
   {
      $component = new FormatComponent(['count' => 1]);

      $this->assertSame(base64_encode(serialize(['count' => 1])), (string) $component);
   }

   public function testInvokeEncodesCallableResult(): void
   {
      $component = new FormatComponent(fn (array $args) => ['value' => $args[0]]);

      $result = $component('ok');

      $this->assertSame(base64_encode(serialize(['value' => 'ok'])), $result);
   }
}
