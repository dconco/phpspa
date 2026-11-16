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
      $dom = '<DummyMethod::Render name="World" />';

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

   public function testFormatRejectsInternalPhpFunctions(): void
   {
      $this->expectException(InvalidArgumentException::class);
      $this->expectExceptionMessage('Internal PHP callable STRTOUPPER cannot be used as a component');

      ComponentTagFormatterProxy::formatPublic('<STRTOUPPER string="proof" />');
   }

   public function testFormatRejectsFrameworkHooksThatReturnObjects(): void
   {
      $this->expectException(InvalidArgumentException::class);
      $this->expectExceptionMessage('Component DummyObjectComponent::__render must return a string');

      ComponentTagFormatterProxy::formatPublic('<DummyObjectComponent />');
   }
   public function testFormatRejectsClassesNotImplementingUIComponent(): void
   {
      $this->expectException(BadFunctionCallException::class);
      $this->expectExceptionMessage('must implement PhpSPA\Interfaces\UIComponent to be used as a component tag');

      ComponentTagFormatterProxy::formatPublic('<PhpSPA.Core.Http.HttpRequest::Session key="test" />');
   }

   public function testFormatRejectsFunctionWithCaseMismatch(): void
   {
      $this->expectException(InvalidArgumentException::class);
      $this->expectExceptionMessage("does not match exact declaration 'DummyFunction'");

      ComponentTagFormatterProxy::formatPublic('<Dummyfunction name="test" />');
   }

   public function testFormatRejectsMethodWithCaseMismatch(): void
   {
      $this->expectException(BadFunctionCallException::class);
      $this->expectExceptionMessage("Method 'RENDER' does not match exact declaration 'Render'");

      ComponentTagFormatterProxy::formatPublic('<DummyMethod::RENDER name="test" />');
   }

   public function testFormatIgnoresTagsWithLowercaseMethodOrNamespace(): void
   {
      $dom1 = '<DummyMethod::render name="test" />';
      $this->assertSame($dom1, ComponentTagFormatterProxy::formatPublic($dom1));

      $dom2 = '<Namespace.class::Method name="test" />';
      $this->assertSame($dom2, ComponentTagFormatterProxy::formatPublic($dom2));
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

final class DummyComponent implements PhpSPA\Interfaces\UIComponent
{
   public static function __render(string $name): string
   {
      return "Hello {$name}";
   }
}

final class DummyMethod implements PhpSPA\Interfaces\UIComponent
{
   public static function Render(string $name): string
   {
      return "Hi {$name}";
   }
}

final class DummyObjectComponent implements PhpSPA\Interfaces\UIComponent
{
   public static function __render(): object
   {
      return (object)['ok' => false];
   }
}

function DummyFunction(string $name): string
{
   return "Func {$name}";
}
