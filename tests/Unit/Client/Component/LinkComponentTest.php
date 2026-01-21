<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LinkComponentTest extends TestCase
{
   public function testLinkRendersAnchorWithAttributes(): void
   {
      $html = Component\Link(children: 'Home', to: '/home', class: 'nav', id: 'link');

      $this->assertStringContainsString('href="/home"', $html);
      $this->assertStringContainsString('data-type="phpspa-link-tag"', $html);
      $this->assertStringContainsString('class="nav"', $html);
      $this->assertStringContainsString('id="link"', $html);
      $this->assertStringContainsString('>Home<', $html);
   }
}
