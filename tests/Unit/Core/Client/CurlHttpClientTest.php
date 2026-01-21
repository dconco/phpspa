<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\CurlHttpClient;

final class CurlHttpClientTest extends TestCase
{
   public function testIsAvailableReturnsBoolean(): void
   {
      $this->assertIsBool(CurlHttpClient::isAvailable());
   }
}
