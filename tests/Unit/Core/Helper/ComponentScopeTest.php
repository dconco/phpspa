<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\ComponentScope;

final class ComponentScopeTest extends TestCase
{
   protected function tearDown(): void
   {
      ComponentScope::clearAll();
   }

   public function testCreateScopeRegistersAndRetrievesCallable(): void
   {
      ComponentScope::createScope();
      ComponentScope::register([
         'getName' => fn () => 'PhpSPA',
      ]);

      $this->assertTrue(ComponentScope::has('getName'));
      $callable = ComponentScope::get('getName');

      $this->assertIsCallable($callable);
      $this->assertSame('PhpSPA', $callable());
   }

   public function testUnregisterRemovesCurrentScopeValue(): void
   {
      ComponentScope::createScope();
      ComponentScope::register([
         'value' => fn () => 'keep',
      ]);

      ComponentScope::unregister('value');

      $this->assertFalse(ComponentScope::has('value'));
   }

   public function testSetCurrentScopeSwitchesRegistry(): void
   {
      $first = ComponentScope::createScope();
      ComponentScope::register([
         'first' => fn () => 'one',
      ]);

      $second = ComponentScope::createScope();
      ComponentScope::register([
         'second' => fn () => 'two',
      ]);

      ComponentScope::setCurrentScope($first);

      $this->assertTrue(ComponentScope::has('first'));
      $this->assertTrue(ComponentScope::has('second'));
   }

   public function testClearRemovesOnlyCurrentScopeValues(): void
   {
      $scope = ComponentScope::createScope();
      ComponentScope::register([
         'value' => fn () => 'clear',
      ]);

      ComponentScope::clear();

      $this->assertFalse(ComponentScope::has('value'));
      ComponentScope::setCurrentScope($scope);
      $this->assertFalse(ComponentScope::has('value'));
   }

   public function testRemoveScopeClearsCurrentScope(): void
   {
      $scope = ComponentScope::createScope();
      ComponentScope::register([
         'value' => fn () => 'remove',
      ]);

      ComponentScope::removeScope($scope);

      $this->assertFalse(ComponentScope::has('value'));
   }
}
