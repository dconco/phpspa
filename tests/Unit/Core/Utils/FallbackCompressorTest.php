<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\FallbackCompressor;

final class FallbackCompressorTest extends TestCase
{
   public function testBasicMinifyRemovesCommentsAndWhitespaceBetweenTags(): void
   {
      $html = "<div>  a </div> <!--comment-->\n   <span> b </span>";
      $minified = FallbackCompressor::basicMinify($html);

      $this->assertStringNotContainsString('<!--comment-->', $minified);
      $this->assertStringNotContainsString('> <', $minified);
      $this->assertStringContainsString('<div> a </div><span> b </span>', $minified);
   }

   public function testMinifyJavaScriptStripsComments(): void
   {
      $js = "var x = 1; // comment\nvar y = 2; /* block */";
      $minified = FallbackCompressor::minifyJavaScript($js);

      $this->assertStringNotContainsString('comment', $minified);
      $this->assertStringNotContainsString('block', $minified);
   }

   public function testAggressiveMinifyStripsAttributesAndWhitespace(): void
   {
      $html = "<div class=\"\">  test </div>\n<p> text </p>";
      $minified = FallbackCompressor::aggressiveMinify($html);

      $this->assertStringNotContainsString('class=""', $minified);
      $this->assertStringNotContainsString("\n", $minified);
   }

   public function testExtremeMinifyRemovesExtraSpaces(): void
   {
      $html = "<div  id = \"a\" > x </div>";
      $minified = FallbackCompressor::extremeMinify($html);

      $this->assertStringContainsString('<div id="a">x</div>', $minified);
   }

   public function testMinifyCssStripsComments(): void
   {
      $css = "body { color: red; } /* comment */";
      $minified = FallbackCompressor::minifyCSS($css);

      $this->assertStringNotContainsString('comment', $minified);
      $this->assertStringContainsString('body{color:red', $minified);
   }

   public function testExtremeMinifyJavaScriptCollapsesWhitespace(): void
   {
      $js = "let x = 1;\nlet y = 2;";
      $minified = FallbackCompressor::extremeMinifyJavaScript($js);

      $this->assertStringContainsString('let x', $minified);
      $this->assertStringContainsString('let y', $minified);
   }

   public function testExtremeMinifyCssCollapsesWhitespace(): void
   {
      $css = "body { margin: 0; }\n/* hi */";
      $minified = FallbackCompressor::extremeMinifyCSS($css);

      $this->assertStringNotContainsString('hi', $minified);
      $this->assertStringNotContainsString("\n", $minified);
   }
}
