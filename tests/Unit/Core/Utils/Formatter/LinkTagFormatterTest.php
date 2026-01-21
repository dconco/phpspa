<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\Formatter\LinkTagFormatter;

final class LinkTagFormatterTest extends TestCase
{
   public function testFormatTransformsLinkTag(): void
   {
      $content = '<Link to="/home" label="Home" class="nav" />';

      LinkTagFormatter::format($content);

      $this->assertSame('<a href="/home" class="nav" data-type="phpspa-link-tag">Home</a>', $content);
   }
}
