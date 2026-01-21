<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PhpSPA\Http\Router;
use PhpSPA\App;

final class RouterTest extends TestCase
{
   public function testCaseSensitiveSetter(): void
   {
      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      $router->caseSensitive(true);

      $this->assertTrue($this->getPrivateProperty($router, 'caseSensitive'));
   }

   public function testMiddlewareAddsHandler(): void
   {
      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      $router->middleware(fn () => null);

      $this->assertCount(1, $this->getPrivateProperty($router, 'middlewares'));
   }

   public function testPrefixInvokesHandlerWhenMatched(): void
   {
      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      Router::$request_uri = '/api/test';

      $called = false;
      $router->prefix('/api', function () use (&$called) {
         $called = true;
      });

      $this->assertTrue($called);
   }

   #[RunInSeparateProcess]
   #[PreserveGlobalState(false)]
   public function testPrefixDoesNotInvokeWhenNotMatchedInFreshProcess(): void
   {
      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      Router::$request_uri = '/other';

      $called = false;
      $router->prefix('/api', function () use (&$called) {
         $called = true;
      });

      $this->assertFalse($called);
   }

   public function testCallWithGetRunsHandlerOnMatch(): void
   {
      App::$request_uri = '/route';
      $_SERVER['REQUEST_METHOD'] = 'GET';

      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      $router->get('/route', fn () => null);

      $this->assertTrue(true);
   }

   public function testCallWithGetDoesNotThrowWhenNoMatch(): void
   {
      App::$request_uri = '/nope';
      $_SERVER['REQUEST_METHOD'] = 'GET';

      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      $router->get('/route', fn () => 'value');

      $this->assertTrue(true);
   }

   public function testCallThrowsOnInvalidMethod(): void
   {
      $this->expectException(BadMethodCallException::class);

      $router = new Router(prefix: '', caseSensitive: false, middlewares: []);
      $router->options('/route', fn () => 'value');
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
