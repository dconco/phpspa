<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Compression\Compressor;

final class HtmlCompressorTest extends TestCase
{
   public function testSetLevelClampsValue(): void
   {
      HtmlCompressorProxy::setLevel(-5);
      $this->assertSame(0, HtmlCompressorProxy::getCompressionLevel());

      HtmlCompressorProxy::setLevel(10);
      $this->assertSame(4, HtmlCompressorProxy::getCompressionLevel());
   }

   public function testSetGzipEnabledUpdatesFlag(): void
   {
      HtmlCompressorProxy::setGzipEnabled(false);
      $this->assertFalse(HtmlCompressorProxy::getGzipEnabled());
   }

   public function testCompressReturnsOriginalWhenLevelNone(): void
   {
      Compressor::setLevel(Compressor::LEVEL_NONE);
      $html = '<div> x </div>';

      $this->assertSame($html, Compressor::compress($html, 'text/html'));
      $this->assertSame('disabled', Compressor::getCompressionEngine());
   }

   public function testSupportsGzipAndCompressJson(): void
   {
      $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
      Compressor::setGzipEnabled(true);

      $this->assertTrue(Compressor::supportsGzip());

      $compressed = Compressor::compressJson(['a' => 1]);
      $decoded = gzdecode($compressed);

      $this->assertSame('{"a":1}', $decoded);
   }

   public function testGzipCompressReturnsGzipPayload(): void
   {
      $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
      Compressor::setGzipEnabled(true);

      $compressed = Compressor::gzipCompress('data', 'text/plain');

      $this->assertStringStartsWith("\x1f\x8b", $compressed);
   }

   public function testCompressWithLevelMinifiesHtml(): void
   {
      $html = "<div>  x </div>\n";
      $minified = Compressor::compressWithLevel($html, Compressor::LEVEL_BASIC, 'HTML');

      $this->assertStringNotContainsString("\n", $minified);
   }

   public function testCompressComponentUsesExtremeLevel(): void
   {
      $html = '<div>x</div>';

      $this->assertSame($html, Compressor::compressComponent('<div>  x  </div>', 'HTML'));
   }

   public function testNoCompression(): void
   {
      $html = '<div>  x  </div>';

      $this->assertSame($html, Compressor::compressWithLevel($html, Compressor::LEVEL_NONE, 'HTML'));
   }

   public function testGetLevelReturnsCurrentLevel(): void
   {
      Compressor::setLevel(Compressor::LEVEL_AGGRESSIVE);

      $this->assertSame(Compressor::LEVEL_AGGRESSIVE, Compressor::getLevel());
   }
}

final class HtmlCompressorProxy
{
   use PhpSPA\Core\Utils\HtmlCompressor;

   public static function getCompressionLevel(): int
   {
      $reader = Closure::bind(
         function (): int {
            return self::$compressionLevel;
         },
         null,
         self::class
      );

      return $reader();
   }

   public static function getGzipEnabled(): bool
   {
      $reader = Closure::bind(
         function (): bool {
            return self::$useGzip;
         },
         null,
         self::class
      );

      return $reader();
   }
}
