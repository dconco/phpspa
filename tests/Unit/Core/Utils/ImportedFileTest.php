<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\ImportedFile;

final class ImportedFileTest extends TestCase
{
   public function testMetadataAccessors(): void
   {
      $content = 'hello';
      $dataUri = 'data:text/plain;base64,' . base64_encode($content);
      $file = new ImportedFile($dataUri, '/tmp/example.txt');

      $this->assertSame('data:text/plain;base64,' . base64_encode($content), (string) $file);
      $this->assertSame('text/plain', $file->getContentType());
      $this->assertSame('example.txt', $file->getFilename());
      $this->assertSame('txt', $file->getExtension());
      $this->assertSame($content, $file->getRawContent());
      $this->assertFalse($file->isImage());
   }

   public function testSaveAsWritesFile(): void
   {
      $content = 'file-data';
      $dataUri = 'data:text/plain;base64,' . base64_encode($content);
      $file = new ImportedFile($dataUri, __DIR__ . '/example.txt');

      $tempPath = tempnam(sys_get_temp_dir(), 'phpspa_import_');

      try {
         $this->assertTrue($file->saveAs($tempPath));
         $this->assertSame($content, file_get_contents($tempPath));
      } finally {
         @unlink($tempPath);
      }
   }
}
