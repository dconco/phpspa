<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Http\Response;

final class ResponseTest extends TestCase
{
   public function testSuccessSetsStatusAndPayload(): void
   {
      $response = new Response();
      $response->success(['id' => 1], 'Done');

      $data = $this->getPrivateProperty($response, 'data');
      $status = $this->getPrivateProperty($response, 'statusCode');

      $this->assertSame(Response::StatusOK, $status);
      $this->assertSame(true, $data['success']);
      $this->assertSame('Done', $data['message']);
      $this->assertSame(['id' => 1], $data['data']);
   }

   public function testValidationErrorSetsStatusAndErrors(): void
   {
      $response = new Response();
      $response->validationError(['field' => 'required']);

      $data = $this->getPrivateProperty($response, 'data');
      $status = $this->getPrivateProperty($response, 'statusCode');

      $this->assertSame(Response::StatusUnprocessableEntity, $status);
      $this->assertSame(false, $data['success']);
      $this->assertSame('Validation failed', $data['message']);
      $this->assertSame(['field' => 'required'], $data['errors']);
   }

   public function testMakeCreatesResponseWithHeaders(): void
   {
      $response = Response::make('payload', Response::StatusAccepted, ['X-Test' => 'yes']);

      $data = $this->getPrivateProperty($response, 'data');
      $status = $this->getPrivateProperty($response, 'statusCode');
      $headers = $this->getPrivateProperty($response, 'headers');

      $this->assertSame('payload', $data);
      $this->assertSame(Response::StatusAccepted, $status);
      $this->assertSame('yes', $headers['X-Test']);
   }

   public function testJsonSetsContentTypeAndData(): void
   {
      $response = new Response();
      $response->json(['ok' => true]);

      $headers = $this->getPrivateProperty($response, 'headers');
      $data = $this->getPrivateProperty($response, 'data');

      $this->assertStringContainsString('application/json', $headers['Content-Type']);
      $this->assertSame(['ok' => true], $data);
   }

   public function testHeaderAndContentType(): void
   {
      $response = new Response();
      $response->header('X-Token', 'value');
      $response->contentType('text/plain');

      $headers = $this->getPrivateProperty($response, 'headers');

      $this->assertSame('value', $headers['X-Token']);
      $this->assertSame('text/plain; charset=utf-8', $headers['Content-Type']);
   }

   public function testCreatedSetsStatusAndPayload(): void
   {
      $response = new Response();
      $response->created(['id' => 2], 'Created');

      $data = $this->getPrivateProperty($response, 'data');
      $status = $this->getPrivateProperty($response, 'statusCode');

      $this->assertSame(Response::StatusCreated, $status);
      $this->assertSame(true, $data['success']);
      $this->assertSame('Created', $data['message']);
      $this->assertSame(['id' => 2], $data['data']);
   }

   public function testErrorHelpersSetStatusAndMessage(): void
   {
      $response = new Response();
      $response->notFound('Missing');

      $data = $this->getPrivateProperty($response, 'data');
      $status = $this->getPrivateProperty($response, 'statusCode');

      $this->assertSame(Response::StatusNotFound, $status);
      $this->assertSame('Missing', $data['message']);

      $response->unauthorized('Nope');
      $status = $this->getPrivateProperty($response, 'statusCode');
      $data = $this->getPrivateProperty($response, 'data');
      $this->assertSame(Response::StatusUnauthorized, $status);
      $this->assertSame('Nope', $data['message']);

      $response->forbidden('Denied');
      $status = $this->getPrivateProperty($response, 'statusCode');
      $data = $this->getPrivateProperty($response, 'data');
      $this->assertSame(Response::StatusForbidden, $status);
      $this->assertSame('Denied', $data['message']);
   }

   public function testPaginateSetsPaginationPayload(): void
   {
      $response = new Response();
      $response->paginate(['a'], 10, 5, 1, 2);

      $data = $this->getPrivateProperty($response, 'data');

      $this->assertSame(true, $data['success']);
      $this->assertSame(['a'], $data['data']);
      $this->assertSame(10, $data['pagination']['total']);
      $this->assertSame(1, $data['pagination']['current_page']);
      $this->assertSame(2, $data['pagination']['last_page']);
   }

   public function testSendFileThrowsWhenMissing(): void
   {
      $response = new Response();

      $this->expectException(InvalidArgumentException::class);

      $response->sendFile(__DIR__ . '/missing.json');
   }

   public function testSendFileLoadsJsonContent(): void
   {
      if (!extension_loaded('fileinfo')) {
         $this->markTestSkipped('fileinfo extension is not enabled.');
      }

      $tempPath = tempnam(sys_get_temp_dir(), 'phpspa_json_');
      $jsonPath = $tempPath . '.json';
      rename($tempPath, $jsonPath);
      file_put_contents($jsonPath, '{"name":"PhpSPA"}');

      try {
         $response = new Response();
         $response->sendFile($jsonPath);

         $data = $this->getPrivateProperty($response, 'data');
         $status = $this->getPrivateProperty($response, 'statusCode');
         $headers = $this->getPrivateProperty($response, 'headers');

         $this->assertSame(Response::StatusOK, $status);
         $this->assertSame('application/json; charset=utf-8', $headers['Content-Type']);
         $this->assertSame('PhpSPA', $data->name ?? null);
      } finally {
         @unlink($jsonPath);
      }
   }

   public function testToStringReturnsJsonWhenContentTypeJson(): void
   {
      $response = new Response();
      $response->contentType('application/json');
      $response->data(['value' => 1]);

      $this->assertSame("{\n    \"value\": 1\n}", (string) $response);
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
}
