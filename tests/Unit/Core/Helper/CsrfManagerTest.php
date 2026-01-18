<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\CsrfManager;
use PhpSPA\Http\Session;

final class CsrfManagerTest extends TestCase
{
   protected function tearDown(): void
   {
      Session::remove('_csrf_tokens');
   }

   public function testGenerateAndVerifyTokenOnce(): void
   {
      $csrf = new CsrfManager('form');
      $token = $csrf->generate();

      $this->assertNotEmpty($token);
      $this->assertTrue($csrf->verifyToken($token));
      $this->assertFalse($csrf->verifyToken($token));
   }

   public function testGetInputReturnsHiddenFieldMarkup(): void
   {
      $csrf = new CsrfManager('form2');
      $input = $csrf->getInput();

      $this->assertStringContainsString('name="form2"', $input);
      $this->assertStringContainsString('value="', $input);
   }

   public function testVerifyReadsTokenFromRequestHeader(): void
   {
      $csrf = new CsrfManager('form3');
      $token = $csrf->generate();

      $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

      $this->assertTrue($csrf->verify());
   }

   public function testGetTokenReturnsStoredToken(): void
   {
      $csrf = new CsrfManager('form4');
      $first = $csrf->getToken();
      $second = $csrf->getToken();

      $this->assertSame($first, $second);
   }
}
