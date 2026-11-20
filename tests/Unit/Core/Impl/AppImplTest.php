<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Impl\RealImpl\AppImpl;
use PhpSPA\Component;
use PhpSPA\Compression\Compressor;

final class AppImplTest extends TestCase
{
   public function testDefaultTargetAndCaseSensitivity(): void
   {
      $app = new TestAppImpl();

      $app->defaultTargetID('root')->defaultToCaseSensitive();

      $this->assertSame('root', $this->getProperty($app, 'defaultTargetID', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
      $this->assertTrue($this->getProperty($app, 'defaultCaseSensitive', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
   }

   public function testAttachAndDetachComponent(): void
   {
      $app = new TestAppImpl();
      $component = new Component(fn () => '<div></div>');
      $component->name('home');

      $app->attach($component);
      $components = $this->getProperty($app, 'components', PhpSPA\Core\Impl\RealImpl\AppImpl::class);

      $this->assertArrayHasKey('home', $components);

      $app->detach($component);
      $components = $this->getProperty($app, 'components', PhpSPA\Core\Impl\RealImpl\AppImpl::class);

      $this->assertArrayNotHasKey('home', $components);
   }

   public function testMiddlewareAndAssetsRegistration(): void
   {
      $app = new TestAppImpl();

      $app->middleware(fn () => null)
         ->script('console.log(1);', 'main')
         ->styleSheet('body{}', 'main')
         ->link('body{}', 'alt', 'text/css', 'stylesheet', ['media' => 'print'])
         ->meta(name: 'description', content: 'test')
         ->compression(Compressor::LEVEL_BASIC, false)
         ->assetCacheHours(4);

      $this->assertCount(1, $this->getProperty($app, 'middlewares', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
      $this->assertCount(1, $this->getProperty($app, 'scripts', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
      $this->assertCount(2, $this->getProperty($app, 'stylesheets', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
      $this->assertCount(1, $this->getProperty($app, 'metadata', PhpSPA\Core\Impl\RealImpl\AppImpl::class));
   }

   public function testCorsMergesConfiguration(): void
   {
      $app = new TestAppImpl();
      $app->cors(['allow_methods' => ['PATCH']]);

      $cors = $this->getProperty($app, 'cors', PhpSPA\Core\Impl\RealImpl\AppImpl::class);

      $this->assertContains('PATCH', $cors['allow_methods']);
   }

   public function testPrefixAndStaticRegistration(): void
   {
      $app = new TestAppImpl();
      $app->prefix('/api', fn () => null)
         ->useStatic('/assets', '/tmp');

      $prefix = $this->getProperty($app, 'prefix', PhpSPA\Core\Impl\RealImpl\AppImpl::class);
      $static = $this->getProperty($app, 'static', PhpSPA\Core\Impl\RealImpl\AppImpl::class);

      $this->assertSame('/api', $prefix[0]['path']);
      $this->assertSame('/assets', $static[0]['route']);
      $this->assertSame('/tmp', $static[0]['staticPath']);
   }

   public function testTargetInformationIncludesComponentScripts(): void
   {
      $_SERVER['REQUEST_METHOD'] = 'GET';
      $_SERVER['REQUEST_URI'] = '/test-route';

      $app = new TestAppImpl();
      $component = new Component(fn () => '<h1>Hello</h1>');
      $component->route('/test-route');
      $component->targetID('app');
      $component->script('console.log("component-script");');

      $app->attach($component);

      $html = $app->run(true);

      $this->assertIsString($html);
      $this->assertMatchesRegularExpression('/phpspa-target-data="([^"]+)"/', $html);

      preg_match('/phpspa-target-data="([^"]+)"/', $html, $matches);
      $targetData = json_decode(base64_decode($matches[1]), true);

      $this->assertIsArray($targetData);
      $this->assertArrayHasKey('scripts', $targetData);
      $this->assertNotEmpty($targetData['scripts']);
      $this->assertStringContainsString('<script', $targetData['scripts'][0]);
   }

   private function getProperty(object $object, string $name, string $className): mixed
   {
      $reader = Closure::bind(
         function () use ($name): mixed {
            return $this->{$name};
         },
         $object,
         $className
      );

      return $reader();
   }
}

final class TestAppImpl extends AppImpl
{
   public function __construct()
   {
      $this->layout = '<html><head></head><body><div id="app"></div></body></html>';
   }
}
