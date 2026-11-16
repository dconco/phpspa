<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\ComponentValueCodec;
use PhpSPA\Http\Session;

final class ComponentValueCodecTest extends TestCase
{
   protected function setUp(): void
   {
      Session::start();
      putenv('APP_KEY=test_app_key_secret_123456');
   }

   protected function tearDown(): void
   {
      putenv('APP_KEY');
      unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);
   }

   public function testEncodeDecodeScalarAndArray(): void
   {
      $data = ['name' => 'Alice', 'role' => 'admin', 'count' => 42];
      $token = ComponentValueCodec::encode($data);

      $this->assertStringStartsWith('phpspa-v1.', $token);

      $valid = false;
      $decoded = ComponentValueCodec::decode($token, $valid);

      $this->assertTrue($valid);
      $this->assertSame($data, $decoded);
   }

   public function testEncodeDecodeObject(): void
   {
      $obj = (object)['title' => 'PhpSPA Component', 'tags' => ['php', 'spa']];
      $token = ComponentValueCodec::encode($obj);

      $valid = false;
      $decoded = ComponentValueCodec::decode($token, $valid);

      $this->assertTrue($valid);
      $this->assertEquals($obj, $decoded);
   }

   public function testDecodeRejectsTamperedPayload(): void
   {
      $token = ComponentValueCodec::encode(['admin' => false]);
      $parts = explode('.', $token);

      // Tamper base64 payload to represent ['admin' => true]
      $tamperedPayload = base64_encode(serialize(['admin' => true]));
      $tamperedToken = $parts[0] . '.' . $tamperedPayload . '.' . $parts[2];

      $valid = false;
      $result = ComponentValueCodec::decode($tamperedToken, $valid);

      $this->assertFalse($valid);
      $this->assertNull($result);
   }

   public function testDecodeRejectsTamperedSignature(): void
   {
      $token = ComponentValueCodec::encode('my-secret-value');
      $parts = explode('.', $token);

      // Invert last character of signature
      $lastChar = $parts[2][63] === '0' ? '1' : '0';
      $tamperedSig = substr($parts[2], 0, 63) . $lastChar;
      $tamperedToken = $parts[0] . '.' . $parts[1] . '.' . $tamperedSig;

      $valid = false;
      $result = ComponentValueCodec::decode($tamperedToken, $valid);

      $this->assertFalse($valid);
      $this->assertNull($result);
   }

   public function testKeyDerivationDiffersWithDifferentAppKey(): void
   {
      putenv('APP_KEY=key_one');
      $token = ComponentValueCodec::encode('secure-data');

      putenv('APP_KEY=key_two');
      $valid = false;
      $result = ComponentValueCodec::decode($token, $valid);

      $this->assertFalse($valid);
      $this->assertNull($result);
   }
}
