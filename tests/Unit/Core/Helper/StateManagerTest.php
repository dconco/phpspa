<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Helper\StateManager;

use const PhpSPA\Core\Impl\Const\STATE_HANDLE;

final class StateManagerTest extends TestCase
{
   protected function setUp(): void
   {
      SessionHandler::set(STATE_HANDLE, []);
      unset($_SERVER['HTTP_X_REQUESTED_WITH']);
   }

   public function testInvokeReturnsDefaultAndUpdatesState(): void
   {
      $state = new StateManager('counter', 0);

      $this->assertSame(0, $state());
      $this->assertSame(5, $state(5));
      $this->assertSame(5, $state());

      $sessionData = SessionHandler::get(STATE_HANDLE);
      $this->assertSame(5, $sessionData['counter']);
   }

   public function testMapConcatenatesArrayValues(): void
   {
      $state = new StateManager('items', ['a', 'b']);

      $result = $state->map(fn ($item, $key) => $item . $key);

      $this->assertSame('a0b1', $result);
   }

   public function testMapThrowsForNonArrayState(): void
   {
      $state = new StateManager('value', 'text');

      $this->expectException(RuntimeException::class);

      $state->map(fn () => '');
   }

   public function testToStringForArrayAndNull(): void
   {
      $arrayState = new StateManager('array', ['a' => 1]);
      $nullState = new StateManager('none', null);

      $this->assertSame('{"a":1}', (string) $arrayState);
      $this->assertSame('', (string) $nullState);
   }
}
