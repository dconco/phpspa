<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FormatBracketInterpolationTest extends TestCase
{
   public function testFormatReplacesInterpolations(): void
   {
      $content = 'Hello {{ name }} {{! remove !}}';

      FormatBracketInterpolationProxy::formatPublic($content);

      $this->assertStringContainsString('<?php print_r(name); ?>', $content);
      $this->assertStringNotContainsString('remove', $content);
   }
}

final class FormatBracketInterpolationProxy extends PhpSPA\Core\Utils\Formatter\FormatBracketInterpolation
{
   public static function formatPublic(string &$content): void
   {
      $instance = new self();
      $instance->format($content);
   }
}
