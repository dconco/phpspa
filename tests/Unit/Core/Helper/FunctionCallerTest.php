<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\FunctionCaller;
use PhpSPA\Http\Session;

use const PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

final class FunctionCallerTest extends TestCase
{
   protected function tearDown(): void
   {
      Session::remove(CALL_FUNC_HANDLE);
   }

   public function testToStringEmbedsEncodedCallableInformation(): void
   {
      $caller = new FunctionCaller('strlen', true);
      $output = (string) $caller;

      $this->assertMatchesRegularExpression("/phpspa\.__call\('([^']+)'\)/", $output);

      preg_match("/phpspa\.__call\('([^']+)'\)/", $output, $matches);
      $token = $matches[1] ?? '';
      $decoded = json_decode(base64_decode($token), true);

      $this->assertIsArray($decoded);
      $this->assertSame('strlen', $decoded[0]);
      $this->assertTrue($decoded[2]);
      $this->assertNotEmpty($decoded[1]);
   }

   public function testInvokeBuildsCallStringWithArguments(): void
   {
      $caller = new FunctionCaller('strlen', false);
      $token = $this->extractToken((string) $caller);

      $output = $caller(1, 'two');

      $this->assertStringStartsWith("phpspa.__call('{$token}'", $output);
      $this->assertStringContainsString(', 1', $output);
      $this->assertStringContainsString(', two', $output);
      $this->assertStringEndsWith(')', $output);
   }

   public function testCallableNameForInstanceMethod(): void
   {
      $instance = new FunctionCallerDummy();
      $caller = new FunctionCaller([$instance, 'run'], false);

      $decoded = $this->decodeToken((string) $caller);

      $this->assertSame(FunctionCallerDummy::class . '::run', $decoded[0]);
   }

   public function testCallableNameForStaticMethod(): void
   {
      $caller = new FunctionCaller([FunctionCallerDummy::class, 'staticRun'], false);

      $decoded = $this->decodeToken((string) $caller);

      $this->assertSame(FunctionCallerDummy::class . '::staticRun', $decoded[0]);
   }

   public function testCallableNameForClosure(): void
   {
      $caller = new FunctionCaller(fn () => 'ok', false);

      $decoded = $this->decodeToken((string) $caller);

      $this->assertSame('Closure', $decoded[0]);
   }

   private function extractToken(string $output): string
   {
      preg_match("/phpspa\.__call\('([^']+)'\)/", $output, $matches);
      return $matches[1] ?? '';
   }

   private function decodeToken(string $output): array
   {
      $token = $this->extractToken($output);
      return json_decode(base64_decode($token), true) ?? [];
   }
}

final class FunctionCallerDummy
{
   public function run(): string
   {
      return 'run';
   }

   public static function staticRun(): string
   {
      return 'static';
   }
}
