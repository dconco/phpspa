<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\StateManager;

final class UseStateHookTest extends TestCase
{
   public function testUseStateReturnsStateManager(): void
   {
      $state = Component\useState('key', 1);

      $this->assertInstanceOf(StateManager::class, $state);
   }

   public function testCreateStateReturnsStateManager(): void
   {
      $state = Component\createState('key2', 'value');

      $this->assertInstanceOf(StateManager::class, $state);
   }
}
