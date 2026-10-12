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

   // ---------------------------------------------------------------------
   // Static file serving — path traversal containment (issue #171)
   // https://github.com/dconco/phpspa/issues/171
   // ---------------------------------------------------------------------

   private string $staticBase;
   private string $secretDir;

   private function makeStaticTree(): void
   {
      // <tmp>/phpspa_static_<uniq>/
      //   public/assets/app.css   <- servable
      //   secret/.env             <- MUST NOT be reachable via traversal
      $root = sys_get_temp_dir()
         . DIRECTORY_SEPARATOR . 'phpspa_static_' . uniqid('', true);

      $this->staticBase = $root . DIRECTORY_SEPARATOR . 'public'
         . DIRECTORY_SEPARATOR . 'assets';
      $this->secretDir = $root . DIRECTORY_SEPARATOR . 'secret';

      mkdir($this->staticBase, 0777, true);
      mkdir($this->secretDir, 0777, true);

      file_put_contents(
         $this->staticBase . DIRECTORY_SEPARATOR . 'app.css',
         'body{color:red}'
      );
      file_put_contents(
         $this->secretDir . DIRECTORY_SEPARATOR . '.env',
         'SECRET=should-never-leak'
      );
   }

   private function cleanupStaticTree(): void
   {
      foreach ([
         $this->staticBase . DIRECTORY_SEPARATOR . 'app.css',
         $this->secretDir . DIRECTORY_SEPARATOR . '.env',
      ] as $f) {
         if (is_file($f)) {
            unlink($f);
         }
      }
      // Remove dirs deepest-first; ignore if not empty / already gone.
      @rmdir($this->staticBase);
      @rmdir($this->secretDir);
      @rmdir(dirname($this->staticBase)); // public/
      @rmdir(dirname($this->staticBase, 2)); // root/
   }

   public function testServesFileInsideStaticRoot(): void
   {
      $this->makeStaticTree();

      try {
         $proxy = new PrefixRouterProxy();
         $proxy->addStatic('/assets', $this->staticBase);
         PrefixRouterProxy::$request_uri = '/assets/app.css';

         $served = $proxy->resolveStaticPathPublic();

         $this->assertSame(
            realpath($this->staticBase . DIRECTORY_SEPARATOR . 'app.css'),
            $served,
            'A legitimate file inside the static root must be served.'
         );
      } finally {
         $this->cleanupStaticTree();
      }
   }

   public function testBlocksTraversalOutsideStaticRoot(): void
   {
      $this->makeStaticTree();

      try {
         $proxy = new PrefixRouterProxy();
         $proxy->addStatic('/assets', $this->staticBase);

         // Equivalent of: curl --path-as-is '/assets/%2e%2e/%2e%2e/secret/.env'
         // after rawurldecode() in HttpRequest::path().
         PrefixRouterProxy::$request_uri = '/assets/../../secret/.env';

         $served = $proxy->resolveStaticPathPublic();

         $this->assertNull(
            $served,
            'Path traversal out of the static root must NOT serve the file.'
         );
      } finally {
         $this->cleanupStaticTree();
      }
   }

   public function testBlocksTraversalToProjectRootFile(): void
   {
      $this->makeStaticTree();

      try {
         // Static root = public/ ; attacker climbs to the project root sibling.
         $publicDir = dirname($this->staticBase); // .../public
         $proxy = new PrefixRouterProxy();
         $proxy->addStatic('/', $publicDir);

         // `/` static mapping + traversal to the secret/.env one level up.
         PrefixRouterProxy::$request_uri = '/../secret/.env';

         $served = $proxy->resolveStaticPathPublic();

         $this->assertNull(
            $served,
            'Root-mapped static dir must still contain traversal attempts.'
         );
      } finally {
         $this->cleanupStaticTree();
      }
   }

   public function testReturnsNullForMissingFile(): void
   {
      $this->makeStaticTree();

      try {
         $proxy = new PrefixRouterProxy();
         $proxy->addStatic('/assets', $this->staticBase);
         PrefixRouterProxy::$request_uri = '/assets/does-not-exist.css';

         $this->assertNull($proxy->resolveStaticPathPublic());
      } finally {
         $this->cleanupStaticTree();
      }
   }
}

final class PrefixRouterProxy
{
   use PhpSPA\Core\Router\PrefixRouter;

   public function handlePrefixPublic(array $prefix): void
   {
      $this->handlePrefix($prefix, []);
   }

   public function addStatic(string $route, string $staticPath): void
   {
      $this->static[] = ['route' => $route, 'staticPath' => $staticPath];
   }

   public function resolveStaticPathPublic(): ?string
   {
      // Exercise the pure containment resolver directly: returns the realpath
      // it WOULD serve on a safe hit, or null when the route misses, the file
      // is absent, or the path escapes the static root. Avoids sendFile()'s
      // header/compression side effects so the test asserts the security
      // decision itself.
      foreach ($this->static as $static) {
         $safe = $this->resolveContainedStaticFile(
            static::$request_uri,
            $static['route'],
            $static['staticPath']
         );
         if ($safe !== null) {
            return $safe;
         }
      }
      return null;
   }
}
