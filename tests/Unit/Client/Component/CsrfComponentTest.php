<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfComponentTest extends TestCase
{
   protected function tearDown(): void
   {
      PhpSPA\Http\Session::remove('_csrf_tokens');
   }

   public function testRenderReturnsHiddenInput(): void
   {
      $component = new Component\Csrf('form');
      $html = $component->__render('form');

      $this->assertStringContainsString('type="hidden"', $html);
      $this->assertStringContainsString('name="form"', $html);
      $this->assertStringContainsString('value="', $html);
   }
}
