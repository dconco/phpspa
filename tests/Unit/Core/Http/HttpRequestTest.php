<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpSPA\Core\Http\HttpRequest;

final class HttpRequestTest extends TestCase
{
   private array $serverBackup;
   private array $getBackup;
   private array $postBackup;
   private array $cookieBackup;
   private array $requestBackup;
   private array $filesBackup;
   private array $sessionBackup;

   protected function setUp(): void
   {
      $this->serverBackup = $_SERVER;
      $this->getBackup = $_GET;
      $this->postBackup = $_POST;
      $this->cookieBackup = $_COOKIE;
      $this->requestBackup = $_REQUEST;
      $this->filesBackup = $_FILES;
      $this->sessionBackup = $_SESSION ?? [];

      $_GET = [];
      $_POST = [];
      $_COOKIE = [];
      $_REQUEST = [];
      $_FILES = [];
      $_SESSION = [];

      $_SERVER['REQUEST_METHOD'] = 'GET';
   }

   protected function tearDown(): void
   {
      $_SERVER = $this->serverBackup;
      $_GET = $this->getBackup;
      $_POST = $this->postBackup;
      $_COOKIE = $this->cookieBackup;
      $_REQUEST = $this->requestBackup;
      $_FILES = $this->filesBackup;
      $_SESSION = $this->sessionBackup;
   }

   public function testUrlQueryParsesValues(): void
   {
      $_SERVER['REQUEST_URI'] = '/?foo=bar&baz=1';

      $request = new HttpRequest();

      $this->assertSame('bar', $request->urlQuery('foo'));
      $this->assertSame(1, $request->urlQuery('baz'));
   }

   public function testInvokeReturnsDefaultWhenMissing(): void
   {
      $_REQUEST = ['present' => 'value'];

      $request = new HttpRequest();

      $this->assertSame('value', $request('present'));
      $this->assertSame('fallback', $request('missing', 'fallback'));
   }

   public function testMagicSetAndGet(): void
   {
      $request = new HttpRequest();
      $request->custom = 'value';

      $this->assertSame('value', $request->custom);
   }

   public function testFilesReturnsAllOrSingle(): void
   {
      $_FILES = [
         'upload' => [
            'name' => 'file.txt',
            'error' => UPLOAD_ERR_OK,
         ],
      ];

      $request = new HttpRequest();

      $this->assertSame($_FILES, $request->files());
      $this->assertSame('file.txt', $request->files('upload')['name']);
      $this->assertNull($request->files('missing'));
   }

   public function testApiKeyReadsHeaderFromServer(): void
   {
      $_SERVER['HTTP_Api-Key'] = 'token';

      $request = new HttpRequest();

      $this->assertSame('token', $request->apiKey('Api-Key'));
   }

   public function testAuthReturnsBasicCredentials(): void
   {
      $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode('user:pass');

      $request = new HttpRequest();
      $auth = $request->auth();

      $this->assertSame('user', $auth->basic['username']);
      $this->assertSame('pass', $auth->basic['password']);
      $this->assertNull($auth->bearer);
   }

   public function testAuthReturnsBearerToken(): void
   {
      $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-value';

      $request = new HttpRequest();
      $auth = $request->auth();

      $this->assertNull($auth->basic);
      $this->assertSame('token-value', $auth->bearer);
   }

   public function testHeaderUsesServerFallback(): void
   {
      $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'Value';

      $request = new HttpRequest();
      $header = $request->header('X-CUSTOM-HEADER');

      $this->assertSame('Value', $header);
   }

   public function testUrlParamsReturnsArrayAndValue(): void
   {
      $request = new HttpRequest(['id' => '10']);

      $this->assertSame(['id' => 10], $request->urlParams());
      $this->assertSame(10, $request->urlParams('id'));
      $this->assertNull($request->urlParams('missing'));
   }

   public function testHeaderReturnsAllHeaders(): void
   {
      $_SERVER['HTTP_X_TEST_HEADER'] = 'one';

      $request = new HttpRequest();
      $headers = $request->header();

      $this->assertSame('one', $headers['X-TEST-HEADER']);
   }

   public function testJsonReturnsNullOnEmptyInput(): void
   {
      $request = new HttpRequest();

      $this->assertNull($request->json());
   }

   public function testGetPostCookieSessionAccessors(): void
   {
      $_GET['page'] = '1';
      $_POST['name'] = 'PhpSPA';
      $_COOKIE['mode'] = 'dark';

      $request = new HttpRequest();

      $this->assertSame(1, $request->get('page'));
      $this->assertSame('PhpSPA', $request->post('name'));
      $this->assertSame('dark', $request->cookie('mode'));
   }

   public function testSessionAccessorReturnsStoredValue(): void
   {
      PhpSPA\Http\Session::set('session_key', 'value', true);

      $request = new HttpRequest();

      $this->assertSame('value', $request->session('session_key'));
   }

   public function testMethodAndIsMethod(): void
   {
      $_SERVER['REQUEST_METHOD'] = 'POST';

      $request = new HttpRequest();

      $this->assertSame('POST', $request->method());
      $this->assertTrue($request->isMethod('post'));
   }

   public function testIpUsesForwardedHeaderWhenAvailable(): void
   {
      $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
      $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

      $request = new HttpRequest();

      $this->assertSame('10.0.0.1', $request->ip());
   }

   public function testIsAjaxDetectsXmlHttpRequest(): void
   {
      $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

      $request = new HttpRequest();

      $this->assertTrue($request->isAjax());
   }

   public function testReferrerAndProtocol(): void
   {
      $_SERVER['HTTP_REFERER'] = 'https://example.com/page';
      $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2';

      $request = new HttpRequest();

      $this->assertSame('https://example.com/page', $request->referrer());
      $this->assertSame('HTTP/2', $request->protocol());
   }

   public function testIsHttpsWithHttpsFlag(): void
   {
      $_SERVER['HTTPS'] = 'on';

      $request = new HttpRequest();

      $this->assertTrue($request->isHttps());
   }

   public function testRequestTimeContentTypeAndLength(): void
   {
      $_SERVER['REQUEST_TIME'] = 1700000000;
      $_SERVER['CONTENT_TYPE'] = 'application/json';
      $_SERVER['CONTENT_LENGTH'] = '123';

      $request = new HttpRequest();

      $this->assertSame(1700000000, $request->requestTime());
      $this->assertSame('application/json', $request->contentType());
      $this->assertSame(123, $request->contentLength());
   }

   public function testCsrfAndRequestedWith(): void
   {
      $_SERVER['HTTP_X_CSRF_TOKEN'] = 'csrf';
      $_SERVER['HTTP_X_REQUESTED_WITH'] = 'PHPSPA_REQUEST';

      $request = new HttpRequest();

      $this->assertSame('csrf', $request->csrf());
      $this->assertSame('PHPSPA_REQUEST', $request->requestedWith());
   }

   public function testUriPathSiteUrlOriginAndSameOrigin(): void
   {
      $_SERVER['REQUEST_URI'] = '/path%20here?x=1';
      $_SERVER['HTTP_HOST'] = 'example.com';
      $_SERVER['HTTP_REFERER'] = 'https://example.com:8080/home';
      $_SERVER['SERVER_NAME'] = 'example.com';
      $_SERVER['HTTPS'] = 'off';
      $_SERVER['SERVER_PORT'] = 80;

      $request = new HttpRequest();

      $this->assertSame('/path here', $request->getUri());
      $this->assertSame('/path here', $request->path());
      $this->assertSame('http://example.com/path here', $request->siteURL());
      $this->assertSame('https://example.com:8080', $request->origin());
      $this->assertTrue($request->isSameOrigin());
   }
}
