<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Router\MapRoute;
use PhpSPA\App;

final class MapRouteTest extends TestCase
{
   protected function setUp(): void
   {
      App::$request_uri = '/home';
      $_SERVER['REQUEST_METHOD'] = 'GET';
   }

   public function testMatchReturnsRouteForExactPath(): void
   {
      $map = new MapRoute('GET', ['/home'], false);

      $result = $map->match();

      $this->assertIsArray($result);
      $this->assertSame('home', $result['route']);
   }

   public function testMatchReturnsParams(): void
   {
      App::$request_uri = '/users/5';

      $map = new MapRoute('GET', ['/users/{id}'], false);
      $result = $map->match();

      $this->assertSame('5', $result['params']['id']);
   }

   public function testMatchRespectsCaseSensitivity(): void
   {
      App::$request_uri = '/Home';

      $map = new MapRoute('GET', ['/home'], false);
      $this->assertIsArray($map->match());

      $mapSensitive = new MapRoute('GET', ['/home'], true);
      $this->assertFalse($mapSensitive->match());
   }

   public function testPatternMatchReturnsRoute(): void
   {
      App::$request_uri = '/files/test.txt';

      $map = new MapRoute('GET', ['files/*'], false, true);

      $this->assertIsArray($map->match());
   }

   public function testMatchStrictTypeCastsParam(): void
   {
      App::$request_uri = '/users/7';

      $map = new MapRoute('GET', ['/users/{id:int}'], false);
      $result = $map->match();

      $this->assertSame(7, $result['params']['id']);
   }
}
