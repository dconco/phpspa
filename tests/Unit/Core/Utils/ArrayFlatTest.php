<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Utils\ArrayFlat;

final class ArrayFlatTest extends TestCase
{
   public function testFlatFlattensOneLevel(): void
   {
      $flat = new ArrayFlat([1, [2, 3], 4]);

      $this->assertSame([1, 2, 3, 4], $flat->flat());
   }

   public function testFlatRecursiveFlattensNestedArrays(): void
   {
      $flat = new ArrayFlat([1, [2, [3, 4]], 5]);

      $this->assertSame([1, 2, 3, 4, 5], $flat->flatRecursive());
   }
}
