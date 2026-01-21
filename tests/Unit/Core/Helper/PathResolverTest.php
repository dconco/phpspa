<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\PathResolver;

final class PathResolverTest extends TestCase
{
   protected function setUp(): void
   {
      PathResolver::setBasePath('');
   }

   public function testSetAndGetBasePath(): void
   {
      PathResolver::setBasePath('/app');

      $this->assertSame('/app', PathResolver::getBasePath());
   }

   public function testGetRelativePathToBase(): void
   {
      $this->assertSame('./', PathResolver::getRelativePathToBase(''));
      $this->assertSame('../', PathResolver::getRelativePathToBase('users'));
      $this->assertSame('../../', PathResolver::getRelativePathToBase('users/profile'));
   }

   public function testGetRelativePathFromUriWithBasePath(): void
   {
      PathResolver::setBasePath('/app');

      $this->assertSame('../', PathResolver::getRelativePathFromUri('/app/users/profile'));
   }

   public function testResolveWithBasePath(): void
   {
      PathResolver::setBasePath('/app');

      $this->assertSame('/app/assets/app.css', PathResolver::resolve('/assets/app.css'));
   }

   public function testNormalizeRemovesDotSegments(): void
   {
      $this->assertSame('a/c', PathResolver::normalize('a/b/../c'));
      $this->assertSame('a/b', PathResolver::normalize('a/./b'));
      $this->assertSame('a/b', PathResolver::normalize('a\\b'));
   }

   public function testBuildAbsoluteUrl(): void
   {
      PathResolver::setBasePath('/app');

      $this->assertSame('https://example.com/app/assets/app.css', PathResolver::buildAbsoluteUrl('assets/app.css', 'https', 'example.com'));
   }

   public function testExtractBasePathFromRequestAndScript(): void
   {
      $base = PathResolver::extractBasePath('/app/dashboard', '/app/public/index.php');

      $this->assertSame('../app/public', $base);
   }

   public function testAutoDetectBasePath(): void
   {
      $_SERVER['REQUEST_URI'] = '/blog/posts';
      $_SERVER['SCRIPT_NAME'] = '/blog/public/index.php';

      $base = PathResolver::autoDetectBasePath();

      $this->assertSame('../blog/public', $base);
      $this->assertSame('../blog/public', PathResolver::getBasePath());
   }

   public function testGetCurrentPathWithoutBasePath(): void
   {
      PathResolver::setBasePath('');

      $this->assertSame('users/profile', PathResolver::getCurrentPath('/users/profile?x=1'));
   }

   public function testResolveWithoutBasePath(): void
   {
      PathResolver::setBasePath('');

      $this->assertSame('/assets/app.css', PathResolver::resolve('/assets/app.css', false));
   }
}
