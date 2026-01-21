<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\ImportedFile;

final class ImportedFileImageTest extends TestCase
{
   public function testIsImageReturnsTrueForImageContentType(): void
   {
      $dataUri = 'data:image/png;base64,' . base64_encode('fake');
      $file = new ImportedFile($dataUri, '/tmp/image.png');

      $this->assertSame('image/png', $file->getContentType());
      $this->assertTrue($file->isImage());
   }
}
