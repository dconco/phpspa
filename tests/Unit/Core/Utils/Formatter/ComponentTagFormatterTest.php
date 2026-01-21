<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ComponentTagFormatterTest extends TestCase
{
   protected function tearDown(): void
   {
      PhpSPA\Core\Helper\ComponentScope::clearAll();
   }

   public function testFormatResolvesRenderMethod(): void
   {
      $dom = '<DummyComponent name="PhpSPA" />';

      $output = ComponentTagFormatterProxy::formatPublic($dom);

      $this->assertSame('Hello PhpSPA', $output);
   }

   public function testFormatResolvesExplicitMethod(): void
   {
      $dom = '<DummyMethod::render name="World" />';

      $output = ComponentTagFormatterProxy::formatPublic($dom);

      $this->assertSame('Hi World', $output);
   }

   public function testFormatResolvesFunctionComponent(): void
   {
      $dom = '<DummyFunction name="Ok" />';

      $output = ComponentTagFormatterProxy::formatPublic($dom);

      $this->assertSame('Func Ok', $output);
   }

   public function testFormatResolvesVariableComponent(): void
   {
      PhpSPA\Core\Helper\ComponentScope::createScope();
      PhpSPA\Core\Helper\ComponentScope::register([
         'VarComp' => fn (string $name) => "Var {$name}",
      ]);

      $dom = '<@VarComp name="Yes" />';

      $output = ComponentTagFormatterProxy::formatPublic($dom);

      $this->assertSame('Var Yes', $output);
   }
}

final class ComponentTagFormatterProxy
{
   use PhpSPA\Core\Utils\Formatter\ComponentTagFormatter;

   public static function formatPublic(string $dom): string
   {
      return self::format($dom);
   }
}

final class DummyComponent
{
   public static function __render(string $name): string
   {
      return "Hello {$name}";
   }
}

final class DummyMethod
{
   public static function render(string $name): string
   {
      return "Hi {$name}";
   }
}

function DummyFunction(string $name): string
{
   return "Func {$name}";
}
