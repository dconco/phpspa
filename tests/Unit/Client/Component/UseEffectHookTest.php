<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Helper\StateManager;

use const PhpSPA\Core\Impl\Const\STATE_HANDLE;

final class UseEffectHookTest extends TestCase
{
   protected function setUp(): void
   {
      SessionHandler::set(STATE_HANDLE, []);
      unset($_SERVER['HTTP_X_REQUESTED_WITH']);
   }

   public function testUseEffectRunsOnFirstRenderWithoutDependencies(): void
   {
      new StateManager('first', 1);

      $called = false;
      Component\useEffect(function () use (&$called) {
         $called = true;
      });

      $this->assertTrue($called);
   }

   public function testUseEffectRunsWhenDependencyChanges(): void
   {
      $state = new StateManager('count', 0);
      $state(1);

      $called = false;
      Component\useEffect(function () use (&$called) {
         $called = true;
      }, [$state]);

      $this->assertTrue($called);
   }

   public function testUseEffectSkipsWhenNoChange(): void
   {
      $state = new StateManager('stable', 1);

      $called = false;
      Component\useEffect(function () use (&$called) {
         $called = true;
      }, [$state]);

      $this->assertFalse($called);
   }

   public function testUseEffectRunsOnEmptyDependencyArrayOnFirstRender(): void
   {
      new StateManager('first_render', 1);

      $called = false;
      Component\useEffect(function () use (&$called) {
         $called = true;
      }, [[]]);

      $this->assertTrue($called);
   }

   public function testUseEffectThrowsForInvalidDependency(): void
   {
      $this->expectException(InvalidArgumentException::class);

      Component\useEffect(function () {
      }, [new stdClass()]);
   }
}
