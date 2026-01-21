<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\ImportedFile;
use PhpSPA\Exceptions\AppException;

final class ImportHookTest extends TestCase
{
   public function testImportReturnsImportedFile(): void
   {
      if (!extension_loaded('fileinfo')) {
         $this->markTestSkipped('fileinfo extension is not enabled.');
      }

      $tempPath = tempnam(sys_get_temp_dir(), 'phpspa_import_');
      file_put_contents($tempPath, 'data');

      try {
         $imported = Component\import($tempPath);

         $this->assertInstanceOf(ImportedFile::class, $imported);
         $this->assertSame('data', $imported->getRawContent());
      } finally {
         @unlink($tempPath);
      }
   }

   public function testImportThrowsForLargeFile(): void
   {
      $this->expectException(AppException::class);

      $tempPath = tempnam(sys_get_temp_dir(), 'phpspa_big_');
      file_put_contents($tempPath, str_repeat('a', 1048577));

      try {
         Component\import($tempPath);
      } finally {
         @unlink($tempPath);
      }
   }
}
