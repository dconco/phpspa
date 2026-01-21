<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HtmlAttrInArrayToStringTest extends TestCase
{
   public function testConvertsArrayToAttributes(): void
   {
      $attributes = Component\HTMLAttrInArrayToString([
         'class' => 'nav',
         'disabled' => true,
      ]);

      $this->assertStringContainsString('class="nav"', $attributes);
      $this->assertStringContainsString('disabled', $attributes);
   }
}
