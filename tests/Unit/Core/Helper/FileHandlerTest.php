<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\FileHandler;

final class FileHandlerTest extends TestCase
{
   public function testFileTypeReturnsFalseForMissingFile(): void
   {
      $this->assertFalse(FileHandler::fileType(__DIR__ . '/missing-file.txt'));
   }

   public function testFileTypeReturnsJsonMimeType(): void
   {
      if (!extension_loaded('fileinfo')) {
         $this->markTestSkipped('fileinfo extension is not enabled.');
      }

      $tempPath = tempnam(sys_get_temp_dir(), 'phpspa_');
      $jsonPath = $tempPath . '.json';

      rename($tempPath, $jsonPath);
      file_put_contents($jsonPath, '{"name":"PhpSPA"}');

      try {
         $type = FileHandler::fileType($jsonPath);
      } finally {
         @unlink($jsonPath);
      }

      $this->assertSame('application/json', $type);
   }
}
