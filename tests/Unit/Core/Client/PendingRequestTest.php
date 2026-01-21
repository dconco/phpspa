<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Client\PendingRequest;
use PhpSPA\Core\Client\ClientResponse;
use PhpSPA\Core\Client\HttpClient;

final class PendingRequestTest extends TestCase
{
   public function testConfiguresOptionsAndHeaders(): void
   {
      $request = new PendingRequest('https://example.com');

      $request
         ->headers(['X-Test' => '1'])
         ->timeout(5)
         ->connectTimeout(2)
         ->verifySSL(false)
         ->withUserAgent('UA')
         ->withCertificate('/tmp/cert.pem')
         ->followRedirects(false, 3)
         ->unixSocket('/tmp/socket')
         ->unixSocketPath('/tmp/socket2')
         ->withOptions(['custom' => 'value']);

      $headers = $this->getPrivateProperty($request, 'headers');
      $options = $this->getPrivateProperty($request, 'options');

      $this->assertSame('1', $headers['X-Test']);
      $this->assertSame(5, $options['timeout']);
      $this->assertSame(2, $options['connect_timeout']);
      $this->assertFalse($options['verify_ssl']);
      $this->assertSame('UA', $options['user_agent']);
      $this->assertSame('/tmp/cert.pem', $options['cert_path']);
      $this->assertSame(false, $options['follow_redirects']);
      $this->assertSame(3, $options['max_redirects']);
      $this->assertSame('/tmp/socket2', $options['unix_socket_path']);
      $this->assertSame('value', $options['custom']);
   }

   public function testResolveIpRejectsInvalidValue(): void
   {
      $request = new PendingRequest('https://example.com');

      $this->expectException(InvalidArgumentException::class);

      $request->resolveIP('v5');
   }

   public function testGetBuildsQueryAndExecutesRequest(): void
   {
      $request = new PendingRequest('https://example.com');
      $fake = new PendingRequestFakeClient();
      $this->setPrivateProperty($request, 'client', $fake);

      $response = $request->get(['q' => '1']);

      $this->assertInstanceOf(ClientResponse::class, $response);
      $this->assertSame('GET', $fake->lastMethod);
      $this->assertStringContainsString('q=1', $fake->lastUrl);
   }

   public function testPostSendsBody(): void
   {
      $request = new PendingRequest('https://example.com');
      $fake = new PendingRequestFakeClient();
      $this->setPrivateProperty($request, 'client', $fake);

      $request->post(['name' => 'PhpSPA']);

      $this->assertSame('POST', $fake->lastMethod);
      $this->assertSame('{"name":"PhpSPA"}', $fake->lastBody);
   }

   private function getPrivateProperty(object $object, string $property): mixed
   {
      $reader = Closure::bind(
         function () use ($property): mixed {
            return $this->{$property};
         },
         $object,
         $object::class
      );

      return $reader();
   }

   private function setPrivateProperty(object $object, string $property, mixed $value): void
   {
      $writer = Closure::bind(
         function () use ($property, $value): void {
            $this->{$property} = $value;
         },
         $object,
         $object::class
      );

      $writer();
   }
}

final class PendingRequestFakeClient implements HttpClient
{
   public string $lastUrl = '';
   public string $lastMethod = '';
   public ?string $lastBody = null;

   public function request(string $url, string $method, array $headers, ?string $body = null, array $options = []): ClientResponse
   {
      $this->lastUrl = $url;
      $this->lastMethod = $method;
      $this->lastBody = $body;

      return new ClientResponse('{"ok":true}', 200, ['HTTP/1.1 200 OK']);
   }

   public static function isAvailable(): bool
   {
      return true;
   }
}
