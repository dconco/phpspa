<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Impl\RealImpl\AppImpl;
use PhpSPA\Component;
use PhpSPA\Core\Helper\CsrfManager;
use PhpSPA\Compression\Compressor;

use const PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

final class AppImplExitTest extends TestCase
{
   public function testRunExitsOnOptionsRequest(): void
   {
      [$status, $output] = $this->runIsolatedPhp(
         <<<'PHP'
         $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
         $_SERVER['REQUEST_URI'] = '/';
         $_SERVER['HTTP_HOST'] = 'example.com';
         $_SERVER['SERVER_NAME'] = 'example.com';
         PhpSPA\Core\Impl\RealImpl\AppImpl::$request_uri = '/';

         $app = new TestExitAppImpl();
         $app->run();
         PHP
      );

      $this->assertSame(0, $status);
      $this->assertSame('', $output);
   }

   public function testRunExitsForPhpSpaCall(): void
   {
      $expected = json_encode(['response' => json_encode('result-ok')]);

      [$status, $output] = $this->runIsolatedPhp(
         <<<'PHP'
         $_SERVER['REQUEST_METHOD'] = 'POST';
         $_SERVER['REQUEST_URI'] = '/';
         $_SERVER['HTTP_HOST'] = 'example.com';
         $_SERVER['SERVER_NAME'] = 'example.com';
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'PHPSPA_REQUEST';
         PhpSPA\Core\Impl\RealImpl\AppImpl::$request_uri = '/';

         $csrf = new PhpSPA\Core\Helper\CsrfManager('AppImplExitFunction', PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE);
         $token = $csrf->getToken();

         $tokenData = base64_encode(json_encode(['AppImplExitFunction', $token, false]));
         $payload = base64_encode(json_encode([
            '__call' => [
               'token' => $tokenData,
               'args' => ['ok'],
            ],
         ]));

         $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $payload;

         $app = new TestExitAppImpl();
         $app->run();
         PHP
      );

      $this->assertSame(0, $status);
      $this->assertSame($expected, $output);
   }

   public function testRunComponentExitsForPhpSpaRequest(): void
   {
      [$status, $output] = $this->runIsolatedPhp(
         <<<'PHP'
         $_SERVER['REQUEST_METHOD'] = 'GET';
         $_SERVER['REQUEST_URI'] = '/';
         $_SERVER['HTTP_HOST'] = 'example.com';
         $_SERVER['SERVER_NAME'] = 'example.com';
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'PHPSPA_REQUEST';
         PhpSPA\Core\Impl\RealImpl\AppImpl::$request_uri = '/';

         PhpSPA\Compression\Compressor::setLevel(PhpSPA\Compression\Compressor::LEVEL_NONE);
         PhpSPA\Compression\Compressor::setGzipEnabled(false);
         unset($_SERVER['HTTP_ACCEPT_ENCODING']);

         $component = new PhpSPA\Component(fn () => '<div>ok</div>');
         $component->route('/');

         $app = new TestExitAppImpl();
         $app->attach($component);
         $app->run();
         PHP
      );

      $this->assertSame(0, $status);
      $this->assertStringContainsString('"content"', $output);
   }

   private function runIsolatedPhp(string $code): array
   {
      $root = dirname(__DIR__, 4);
      $autoload = $root . '/vendor/autoload.php';

      $bootstrap = <<<'PHP'
      class TestExitAppImpl extends PhpSPA\Core\Impl\RealImpl\AppImpl
      {
         public function __construct()
         {
            $this->layout = '<html><head></head><body><div id="app"></div></body></html>';
         }
      }

      function AppImplExitFunction(string $value): string
      {
         return 'result-' . $value;
      }
      PHP;

      $script = tempnam(sys_get_temp_dir(), 'phpspa_exit_') . '.php';
      $scriptBody = "<?php\nrequire " . var_export($autoload, true) . ";\n" . $bootstrap . "\n" . $code;

      file_put_contents($script, $scriptBody);

      $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
      $outputLines = [];
      $status = 0;

      exec($command, $outputLines, $status);

      @unlink($script);

      return [$status, implode("\n", $outputLines)];
   }
}
