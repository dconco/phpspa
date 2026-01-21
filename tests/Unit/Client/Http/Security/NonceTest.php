<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Http\Security\Nonce;

final class NonceTest extends TestCase
{
   protected function tearDown(): void
   {
      Nonce::disable();
   }

   public function testNonceDisabledReturnsNull(): void
   {
      Nonce::disable();

      $this->assertNull(Nonce::nonce());
      $this->assertSame('', Nonce::attr());
   }

   public function testEnableGeneratesNonceAndAttribute(): void
   {
      Nonce::enable();
      $nonce = Nonce::nonce();

      $this->assertNotEmpty($nonce);
      $this->assertStringContainsString($nonce, Nonce::attr());
   }

   public function testEnableMergesCustomSources(): void
   {
      Nonce::enable([
         'script-src' => ['https://cdn.example.com'],
      ]);

      $directives = $this->getDirectives();

      $this->assertContains('https://cdn.example.com', $directives['script-src']);
   }

   private function getDirectives(): array
   {
      $getter = Closure::bind(function () {
         return self::$directives;
      }, null, Nonce::class);

      return $getter();
   }
}
