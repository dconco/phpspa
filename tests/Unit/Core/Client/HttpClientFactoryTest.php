<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\HttpClientFactory;
use PhpSPA\Core\Client\HttpClient;

final class HttpClientFactoryTest extends TestCase
{
   public function testResetClearsInstance(): void
   {
      $client = HttpClientFactory::create();
      $this->assertInstanceOf(HttpClient::class, $client);

      HttpClientFactory::reset();

      $client2 = HttpClientFactory::create();
      $this->assertInstanceOf(HttpClient::class, $client2);
   }
}
