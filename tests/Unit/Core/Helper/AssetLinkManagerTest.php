<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Helper\AssetLinkManager;
use PhpSPA\Http\Session;

final class AssetLinkManagerTest extends TestCase
{
   protected function setUp(): void
   {
      Session::start();
      $_SERVER['REQUEST_URI'] = '/';
      $_SERVER['SCRIPT_NAME'] = '/index.php';
   }

   protected function tearDown(): void
   {
      // No session cleanup needed in stateless mode
   }

   public function testGenerateCssAndJsLinks(): void
   {
      $css = AssetLinkManager::generateCssLink('/test', 0, 0);
      $js = AssetLinkManager::generateJsLink('/test', 1, 0);

      $this->assertMatchesRegularExpression('/phpspa\/assets\/[^\/]+\.css$/', $css);
      $this->assertMatchesRegularExpression('/phpspa\/assets\/[^\/]+\.js$/', $js);
   }

   public function testResolveAssetRequestReturnsMapping(): void
   {
      $css = AssetLinkManager::generateCssLink('/test', 0, 0);
      $path = parse_url($css, PHP_URL_PATH);

      $resolved = AssetLinkManager::resolveAssetRequest($path);

      $this->assertSame('/test', $resolved['componentRoute']);
      $this->assertSame('css', $resolved['assetType']);
      $this->assertSame(0, $resolved['assetIndex']);
   }

   public function testCacheConfigRoundTrip(): void
   {
      AssetLinkManager::setCacheConfig(2);
      $config = AssetLinkManager::getCacheConfig();

      $this->assertSame(2, $config['hours']);
      $this->assertArrayHasKey('timestamp', $config);
   }
}
