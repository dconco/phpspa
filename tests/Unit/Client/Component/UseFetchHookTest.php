<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\PendingRequest;

final class UseFetchHookTest extends TestCase
{
   public function testUseFetchReturnsPendingRequest(): void
   {
      $request = Component\useFetch('https://example.com');

      $this->assertInstanceOf(PendingRequest::class, $request);
   }

   public function testUseFetchStoresUrl(): void
   {
      $request = Component\useFetch('https://example.com/path');

      $reader = Closure::bind(
         function (): string {
            return $this->url;
         },
         $request,
         $request::class
      );

      $this->assertSame('https://example.com/path', $reader());
   }
}
