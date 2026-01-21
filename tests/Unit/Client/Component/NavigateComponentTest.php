<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\Enums\NavigateState;
use PhpSPA\Http\Security\Nonce;

final class NavigateComponentTest extends TestCase
{
   protected function tearDown(): void
   {
      Nonce::disable();
   }

   public function testNavigateGeneratesScript(): void
   {
      Nonce::disable();
      $html = Component\Navigate('/path', NavigateState::REPLACE);

      $this->assertStringContainsString('phpspa.navigate("/path", "replace")', $html);
      $this->assertStringContainsString('<script', $html);
   }

   public function testNavigateAcceptsStringState(): void
   {
      $html = Component\Navigate('/home', 'push');

      $this->assertStringContainsString('phpspa.navigate("/home", "push")', $html);
   }
}
