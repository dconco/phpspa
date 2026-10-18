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

   public function testMatchLiteralPrefixStripsPrefixFromValue(): void
   {
      App::$request_uri = '/profile/@john';

      $map = new MapRoute('GET', ['/profile/@{username}'], false);
      $result = $map->match();

      $this->assertIsArray($result);
      $this->assertSame('john', $result['params']['username']);
   }

   public function testMatchLiteralPrefixRequiresThePrefix(): void
   {
      // incoming segment lacks the required "@" prefix -> no match
      App::$request_uri = '/profile/john';

      $map = new MapRoute('GET', ['/profile/@{username}'], false);

      $this->assertFalse($map->match());
   }

   public function testMatchLiteralPrefixWithStrictType(): void
   {
      App::$request_uri = '/order/#42';

      $map = new MapRoute('GET', ['/order/#{id:int}'], false);
      $result = $map->match();

      $this->assertIsArray($result);
      $this->assertSame(42, $result['params']['id']);
   }

   public function testMatchLiteralSuffixStripsSuffixFromValue(): void
   {
      App::$request_uri = '/download/report.json';

      $map = new MapRoute('GET', ['/download/{file}.json'], false);
      $result = $map->match();

      $this->assertIsArray($result);
      $this->assertSame('report', $result['params']['file']);
   }

   public function testMatchLiteralSuffixRequiresTheSuffix(): void
   {
      // incoming segment lacks the required ".json" suffix -> no match
      App::$request_uri = '/download/report.xml';

      $map = new MapRoute('GET', ['/download/{file}.json'], false);

      $this->assertFalse($map->match());
   }

   public function testMatchLiteralPrefixAndSuffixTogether(): void
   {
      App::$request_uri = '/tag/[php]';

      $map = new MapRoute('GET', ['/tag/[{name}]'], false);
      $result = $map->match();

      $this->assertIsArray($result);
      $this->assertSame('php', $result['params']['name']);
   }

   public function testMatchLiteralPrefixRejectsEmptyCapture(): void
   {
      // only the literal, nothing captured -> no match
      App::$request_uri = '/profile/@';

      $map = new MapRoute('GET', ['/profile/@{username}'], false);

      $this->assertFalse($map->match());
   }

   public function testPlainParamStillWorksAfterPrefixSupport(): void
   {
      // regression: a param with no literal prefix/suffix behaves exactly as before
      App::$request_uri = '/users/5';

      $map = new MapRoute('GET', ['/users/{id}'], false);
      $result = $map->match();

      $this->assertSame('5', $result['params']['id']);
   }
}
