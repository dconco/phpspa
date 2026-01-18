<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\ClientResponse;

final class ClientResponseTest extends TestCase
{
   public function testResponseAccessors(): void
   {
      $response = new ClientResponse('{"name":"PhpSPA"}', 200, ['HTTP/1.1 200 OK', 'Content-Type: application/json']);

      $this->assertSame(['name' => 'PhpSPA'], $response->json());
      $this->assertSame('{"name":"PhpSPA"}', $response->text());
      $this->assertSame(200, $response->status());
      $this->assertTrue($response->ok());
      $this->assertFalse($response->failed());
      $this->assertNull($response->error());
      $this->assertSame('application/json', $response->headers()['Content-Type']);
      $this->assertTrue(isset($response->name));
      $this->assertSame('PhpSPA', $response->name);
   }

   public function testFailedResponse(): void
   {
      $response = new ClientResponse(false, 0, [], 'error');

      $this->assertTrue($response->failed());
      $this->assertSame('error', $response->error());
   }
}
