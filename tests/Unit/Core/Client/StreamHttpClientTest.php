<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\StreamHttpClient;

final class StreamHttpClientTest extends TestCase
{
   public function testIsAvailable(): void
   {
      $this->assertTrue(StreamHttpClient::isAvailable());
   }

   public function testRequestReadsDataStream(): void
   {
      $client = new StreamHttpClient();
      $response = $client->request('data://text/plain,hello', 'GET', []);

      $this->assertSame('hello', $response->text());
   }
}
