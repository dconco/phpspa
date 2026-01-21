<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Impl\RealImpl\ComponentImpl;

final class ComponentImplTest extends TestCase
{
   public function testCallSetsBasicProperties(): void
   {
      $component = new TestComponentImpl(fn () => 'ok');

      $component
         ->title('Title')
         ->method('GET', 'POST')
         ->route('/home')
         ->name('home')
         ->targetID('root')
         ->reload(5)
         ->pattern()
         ->exact()
         ->caseSensitive();

      $this->assertSame('Title', $this->getProperty($component, 'title'));
      $this->assertSame('GET|POST', $this->getProperty($component, 'method'));
      $this->assertSame(['/home'], $this->getProperty($component, 'route'));
      $this->assertSame('home', $this->getProperty($component, 'name'));
      $this->assertSame('root', $this->getProperty($component, 'targetID'));
      $this->assertSame(5, $this->getProperty($component, 'reloadTime'));
      $this->assertTrue($this->getProperty($component, 'pattern'));
      $this->assertTrue($this->getProperty($component, 'exact'));
      $this->assertTrue($this->getProperty($component, 'caseSensitive'));
   }

   public function testScriptAndStyleSheetRegistration(): void
   {
      $component = new TestComponentImpl(fn () => 'ok');

      $component->script('console.log(1);', 'main');
      $component->styleSheet('body{}', 'main', 'text/css', 'stylesheet', ['media' => 'screen']);

      $scripts = $this->getProperty($component, 'scripts');
      $styles = $this->getProperty($component, 'stylesheets');

      $this->assertSame('main', $scripts[0]['name']);
      $this->assertSame('text/javascript', $scripts[0]['type']);
      $this->assertSame('screen', $styles[0]['media']);
      $this->assertSame('text/css', $styles[0]['rel']);
   }

   public function testMetaAddsEntry(): void
   {
      $component = new TestComponentImpl(fn () => 'ok');
      $component->meta(name: 'description', content: 'Hello');

      $metadata = $this->getProperty($component, 'metadata');

      $this->assertSame('description', $metadata[0]['name']);
      $this->assertSame('Hello', $metadata[0]['content']);
   }

   public function testRenderFormatsOutput(): void
   {
      $result = TestComponentImpl::Render(fn () => '<div>ok</div>');

      $this->assertSame('<div>ok</div>', $result);
   }

   private function getProperty(object $object, string $name): mixed
   {
      $reader = Closure::bind(
         function () use ($name): mixed {
            return $this->{$name};
         },
         $object,
         $object::class
      );

      return $reader();
   }
}

final class TestComponentImpl extends ComponentImpl
{
   public function __construct(callable $component)
   {
      $this->component = $component;
   }
}
