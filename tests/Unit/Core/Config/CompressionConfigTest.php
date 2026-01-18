<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Compression\Compressor;

final class CompressionConfigTest extends TestCase
{
   protected function tearDown(): void
   {
      unset($_ENV['APP_ENV'], $_SERVER['APP_ENV'], $_SERVER['HTTP_HOST']);
   }

   public function testInitializeSetsDevelopmentDefaults(): void
   {
      CompressionConfig::initialize(Compressor::ENV_DEVELOPMENT);

      $this->assertSame(Compressor::LEVEL_NONE, Compressor::getLevel());
      $this->assertFalse($this->getGzipEnabled());
   }

   public function testInitializeSetsStagingDefaults(): void
   {
      CompressionConfig::initialize(Compressor::ENV_STAGING);

      $this->assertSame(Compressor::LEVEL_BASIC, Compressor::getLevel());
      $this->assertTrue($this->getGzipEnabled());
   }

   public function testCustomSetsLevelAndGzip(): void
   {
      CompressionConfig::custom(Compressor::LEVEL_EXTREME, false);

      $this->assertSame(Compressor::LEVEL_EXTREME, Compressor::getLevel());
      $this->assertFalse($this->getGzipEnabled());
   }

   public function testAutoDetectUsesEnvironment(): void
   {
      $_ENV['APP_ENV'] = 'development';

      CompressionConfig::autoDetect();

      $this->assertSame(Compressor::LEVEL_NONE, Compressor::getLevel());
   }

   public function testGetInfoReportsEnvironment(): void
   {
      $_ENV['APP_ENV'] = 'staging';
      $info = CompressionConfig::getInfo();

      $this->assertSame(Compressor::ENV_STAGING, $info['environment']);
      $this->assertArrayHasKey('gzip_supported', $info);
   }

   private function getGzipEnabled(): bool
   {
      $reader = Closure::bind(
         function (): bool {
            return self::$useGzip;
         },
         null,
         Compressor::class
      );

      return $reader();
   }
}
