<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class PrefixRouterTest extends TestCase
{
   public function testHandlePrefixInvokesHandler(): void
   {
      $proxy = new PrefixRouterProxy();
      PrefixRouterProxy::$request_uri = '/api/test';

      $called = false;
      $proxy->handlePrefixPublic([
         'path' => '/api',
         'handler' => function () use (&$called) {
            $called = true;
         },
      ]);

      $this->assertTrue($called);
   }

   #[RunInSeparateProcess]
   #[PreserveGlobalState(false)]
   public function testHandlePrefixSkipsWhenNoMatchInFreshProcess(): void
   {
      $proxy = new PrefixRouterProxy();
      PrefixRouterProxy::$request_uri = '/other';

      $called = false;
      $proxy->handlePrefixPublic([
         'path' => '/api',
         'handler' => function () use (&$called) {
            $called = true;
         },
      ]);

      $this->assertFalse($called);
   }
}

final class PrefixRouterProxy
{
   use PhpSPA\Core\Router\PrefixRouter;

   public function handlePrefixPublic(array $prefix): void
   {
      $this->handlePrefix($prefix, []);
   }
}
