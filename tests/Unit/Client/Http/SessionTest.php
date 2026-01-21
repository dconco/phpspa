<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Http\Session;

final class SessionTest extends TestCase
{
   protected function tearDown(): void
   {
      Session::remove(['phpspa_session_key', 'phpspa_session_other']);
   }

   public function testSetGetHasAndRemove(): void
   {
      $this->assertTrue(Session::start());

      Session::set('phpspa_session_key', 'value');
      Session::set('phpspa_session_other', 'other');

      $this->assertTrue(Session::has('phpspa_session_key'));
      $this->assertSame('value', Session::get('phpspa_session_key'));

      Session::remove('phpspa_session_key');

      $this->assertFalse(Session::has('phpspa_session_key'));
      $this->assertSame('other', Session::get('phpspa_session_other'));
   }

   public function testIsActiveAndRegenerateId(): void
   {
      Session::start();

      $this->assertTrue(Session::isActive());
      $this->assertTrue(Session::regenerateId());
   }

   public function testDestroyEndsSession(): void
   {
      Session::start();

      $this->assertTrue(Session::destroy());
      $this->assertFalse(Session::isActive());
   }
}
