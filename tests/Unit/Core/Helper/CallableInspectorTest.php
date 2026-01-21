<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\CallableInspector;

final class CallableInspectorTest extends TestCase
{
   private const STATIC_VALUE = 'static-value';

   private string $instanceValue = 'instance-value';

   public function testHasParamDetectsExistingParameter(): void
   {
      $callable = function (string $name, int $age): void {
      };

      $this->assertTrue(CallableInspector::hasParam($callable, 'name'));
      $this->assertFalse(CallableInspector::hasParam($callable, 'missing'));
   }

   public function testGetPropertyReadsStaticValueWithClassName(): void
   {
      $this->assertSame(self::STATIC_VALUE, CallableInspector::getProperty(self::class, 'staticValue'));
   }

   public function testGetPropertyReadsInstanceValueWithObject(): void
   {
      $instance = new class {
         public string $instanceValue = 'instance-value';
      };

      $this->assertSame('instance-value', CallableInspector::getProperty($instance, 'instanceValue'));
   }

   public function testGetPropertyThrowsWhenStaticAccessForInstanceProperty(): void
   {
      $this->expectException(LogicException::class);

      CallableInspector::getProperty(self::class, 'instanceValue');
   }

   private static string $staticValue = self::STATIC_VALUE;
}
