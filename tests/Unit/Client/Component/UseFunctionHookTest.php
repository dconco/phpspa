<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\FunctionCaller;

final class UseFunctionHookTest extends TestCase
{
   public function testUseFunctionReturnsFunctionCaller(): void
   {
      $caller = Component\useFunction('strlen');

      $this->assertInstanceOf(FunctionCaller::class, $caller);
      $this->assertStringContainsString('phpspa.__call', (string) $caller);
   }
}
