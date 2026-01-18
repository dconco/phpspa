<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Http\Session;

final class SessionHandlerTest extends TestCase
{
   protected function tearDown(): void
   {
      Session::remove('phpspa_test');
   }

   public function testGetReturnsEmptyArrayByDefault(): void
   {
      $value = SessionHandler::get('phpspa_test');

      $this->assertSame([], $value);
   }

   public function testSetAndGetRoundTrip(): void
   {
      $payload = [
         'name' => 'PhpSPA',
         'version' => '2.0',
         'features' => ['components', 'routing'],
      ];

      SessionHandler::set('phpspa_test', $payload);

      $this->assertSame($payload, SessionHandler::get('phpspa_test'));
   }
}
