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

      $this->assertMatchesRegularExpression('/phpspa\/assets\/(?:[^\/]+\/)+[^\/]+\.css$/', $css);
      $this->assertMatchesRegularExpression('/phpspa\/assets\/(?:[^\/]+\/)+[^\/]+\.js$/', $js);
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

   // ==================== COMBINED ASSETS (combineAssetsPerComponent) ====================

   public function testGenerateCombinedCssAndJsLinks(): void
   {
      $css = AssetLinkManager::generateCombinedCssLink('/dashboard', [0, 1, 2], 1234567890);
      $js = AssetLinkManager::generateCombinedJsLink('/dashboard', [0, 1], 1234567890);

      $this->assertMatchesRegularExpression('/phpspa\/assets\/(?:[^\/]+\/)+[^\/]+\.css$/', $css);
      $this->assertMatchesRegularExpression('/phpspa\/assets\/(?:[^\/]+\/)+[^\/]+\.js$/', $js);
   }

   public function testCombinedLinkHasNoNameLabel(): void
   {
      // Combined bundles have no single name, so the link must be hash-only
      // (no "{name}-" prefix before the hash segment).
      $css = AssetLinkManager::generateCombinedCssLink('/dashboard', [0, 1], 0);
      $path = parse_url($css, PHP_URL_PATH);

      $resolved = AssetLinkManager::resolveAssetRequest($path);

      $this->assertNotNull($resolved);
      $this->assertNull($resolved['name']);
   }

   public function testResolveCombinedCssReturnsIndexesArray(): void
   {
      $indexes = [0, 2, 5];
      $css = AssetLinkManager::generateCombinedCssLink('/profile', $indexes, 999);
      $path = parse_url($css, PHP_URL_PATH);

      $resolved = AssetLinkManager::resolveAssetRequest($path);

      $this->assertSame('/profile', $resolved['componentRoute']);
      $this->assertSame('css', $resolved['assetType']);
      $this->assertSame($indexes, $resolved['assetIndexes']);
      // Single-index key is null for a combined link
      $this->assertNull($resolved['assetIndex']);
   }

   public function testResolveCombinedJsReturnsIndexesAndScriptType(): void
   {
      $indexes = [1, 3];
      $js = AssetLinkManager::generateCombinedJsLink('/chat', $indexes, 555, 'module');
      $path = parse_url($js, PHP_URL_PATH);

      $resolved = AssetLinkManager::resolveAssetRequest($path);

      $this->assertSame('/chat', $resolved['componentRoute']);
      $this->assertSame('js', $resolved['assetType']);
      $this->assertSame($indexes, $resolved['assetIndexes']);
      $this->assertSame('module', $resolved['scriptType']);
   }

   public function testSingleIndexLinkStillReturnsNullIndexes(): void
   {
      // Backward compatibility: a classic single-index link must NOT carry
      // assetIndexes (so serveAsset's combined branch is never taken for it).
      $css = AssetLinkManager::generateCssLink('/legacy', 0, 0);
      $path = parse_url($css, PHP_URL_PATH);

      $resolved = AssetLinkManager::resolveAssetRequest($path);

      $this->assertSame(0, $resolved['assetIndex']);
      $this->assertNull($resolved['assetIndexes']);
   }

   public function testCombinedLinkTamperDetection(): void
   {
      $css = AssetLinkManager::generateCombinedCssLink('/secure', [0, 1], 0);
      $path = parse_url($css, PHP_URL_PATH);

      // Flip a character inside the encoded segment to break the HMAC
      $tampered = preg_replace_callback(
         '/([A-Za-z0-9_-]+)~/',
         fn($m) => (($m[1][0] === 'A' ? 'B' : 'A') . substr($m[1], 1)) . '~',
         $path
      );

      $resolved = AssetLinkManager::resolveAssetRequest($tampered);

      $this->assertNull($resolved, 'Tampered combined link must fail HMAC verification');
   }

   public function testCombinedLinkVersionDiffersWithMtime(): void
   {
      // Different max-mtime must produce a different encoded link (cache busting).
      AssetLinkManager::setCacheConfig(0); // no bucket rounding, time() used as created
      $a = AssetLinkManager::generateCombinedCssLink('/v', [0, 1], 1000);
      $b = AssetLinkManager::generateCombinedCssLink('/v', [0, 1], 2000);

      $this->assertNotSame(
         parse_url($a, PHP_URL_PATH),
         parse_url($b, PHP_URL_PATH),
         'A changed max(filemtime) must change the combined link'
      );
   }
}
