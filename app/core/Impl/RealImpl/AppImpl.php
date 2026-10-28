<?php

namespace PhpSPA\Core\Impl\RealImpl;

use PhpSPA\DOM;
use PhpSPA\Component;
use PhpSPA\Http\Request;
use PhpSPA\Http\Session;
use PhpSPA\Http\Security\Nonce;
use PhpSPA\Core\Router\MapRoute;
use PhpSPA\Core\Router\PrefixRouter;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Compression\OxcCompressor;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Core\Helper\CsrfManager;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Helper\CallableInspector;
use PhpSPA\Core\Helper\ComponentScope;
use PhpSPA\Core\Helper\AssetLinkManager;
use PhpSPA\Core\Helper\PathResolver;
use PhpSPA\Core\Utils\FileFunction;
use PhpSPA\Core\Utils\Formatter\ComponentTagFormatter;
use PhpSPA\Core\Utils\Validate;
use PhpSPA\Core\Utils\Timer;
use PhpSPA\Http\Response;
use PhpSPA\Interfaces\ApplicationContract;
use PhpSPA\Interfaces\IComponent;
use ReflectionFunction;

use function Component\HTMLAttrInArrayToString;

use const PhpSPA\Core\Impl\Const\STATE_HANDLE;
use const PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;


/**
 * Core application implementation class
 * This abstract class provides the foundational implementation for the PhpSPA application framework.
 * It handles layout management, component registration,
 * routing, and rendering logic that powers the single-page application experience.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @abstract
 */
abstract class AppImpl implements ApplicationContract {
   use PrefixRouter;
   use ComponentTagFormatter;

   /**
    * The layout of the application.
    *
    * @var callable|string $layout
    */
   protected $layout;

   /**
    * The default target ID where the application will render its content.
    *
    * @var string
    */
   private string $defaultTargetID = 'app';

   /**
    * Stores the list of application components.
    * Each component can be accessed and managed by the application core.
    * Typically used for dependency injection or service management.
    *
    * @var array<Component|IComponent>
    */
   private array $components = [];

   /**
    * @var callable|string[]
    */
   private array $middlewares = [];

   /**
    * Holds the data that has been rendered.
    *
    * This property is used to store data that has already been processed or rendered
    * by the application, allowing for reuse or reference without reprocessing.
    *
    * @var mixed
    */
   private $renderedData;

   private array $cors = [];

   /**
    * Global scripts to be executed for the application.
    * These scripts will be included on every component render.
    *
    * @var array<array{
    *    content: callable|string,
    *    name: string|null,
    *    type: string|null
    * }>
    */
   protected array $scripts = [];

   /**
    * Global stylesheets to be included for the application.
    * These styles will be included on every component render.
    *
    * @var array<array{
    *    content: callable|string,
    *    name: string|null,
    *    type: string|null
    * }>
    */
   protected array $stylesheets = [];

   /**
    * Global meta tags registered on the application.
    *
    * @var array<int, array<string, mixed>>
    */
   protected array $metadata = [];

   /**
    * @var array<array{
    *    path: string,
    *    handler: callable|string
    * }>
    */
   protected array $prefix = [];

   private int $disableLogs = 0;

   private bool $module = false;

   private bool $randomizeAssetName = false;

   private bool $combineAssets = false;

   /**
    * Memoized result of resolving the asset URL for the current request:
    *   - array : the decoded, HMAC-verified asset info (reused by every App);
    *   - false : the request is NOT an asset;
    *   - null  : not yet computed.
    * Keyed by `$resolvedAssetUri` so a different request never reuses it.
    *
    * @var array|false|null
    */
   private static array|false|null $resolvedAssetInfo = null;

   /**
    * The request URI `$resolvedAssetInfo` was decoded for; the memo is reused
    * only when it matches the current request.
    *
    * @var string|null
    */
   private static ?string $resolvedAssetUri = null;

   /**
    * Lazily-computed, content-derived global-asset scope id for THIS App (a hash
    * of its registered globals' file/line/mtime signatures). Null until first
    * computed by `globalScopeId()`. Position-independent, so removing another
    * App never changes this one's id. See `globalScopeId()`.
    *
    * @var string|null
    */
   private ?string $scopeIdCache = null;

   private ?string $generatedCacheDirectory = null;

   private bool $useEsbuild = false;

   private bool $useOxc = false;

   /**
    * @var array<string,string>
    */
   private static array $staticFilesCache = [];

   public function defaultTargetID (string $targetID): ApplicationContract
   {
      $this->defaultTargetID = $targetID;
      return $this;
   }

   public function defaultToCaseSensitive (): ApplicationContract
   {
      $this->defaultCaseSensitive = true;
      return $this;
   }

   public function attach (IComponent|Component $component): ApplicationContract
   {
      $name = CallableInspector::getProperty($component, 'name');

      if ($name)
         $this->components[$name] = $component;
      else
         $this->components[] = $component;

      return $this;
   }

   public function detach (IComponent|Component $component): ApplicationContract
   {
      $key = array_search($component, $this->components, true);

      if ($key !== false) {
         unset($this->components[$key]);
      }
      return $this;
   }

   public function middleware (callable|string $middleware): ApplicationContract
   {
      $this->middlewares[] = $middleware;
      return $this;
   }

   public function cors (array $data = []): ApplicationContract
   {
      $this->cors = require dirname(__DIR__, 2) . '/Config/Cors.php';

      /*if (!empty($data)) {
         $this->cors = array_merge_recursive($this->cors, $data);
      }

      foreach ($this->cors as $key => $value) {
         if (\is_array($value)) {
            $this->cors[$key] = array_unique($value);
         }
      }*/

      if (!empty($data)) {
         $this->cors = array_merge($this->cors, $data);
      }

      return $this;
   }

   public function compression (int $level, bool $gzip = true): ApplicationContract
   {
      CompressionConfig::custom($level, $gzip);
      return $this;
   }

   public function compressionEnvironment (string $environment): ApplicationContract
   {
      CompressionConfig::initialize($environment);
      return $this;
   }

   public function assetCacheHours (int $hours): ApplicationContract
   {
      AssetLinkManager::setCacheConfig($hours);
      return $this;
   }

   public function setCustomCompressorLibraryPath(string $path): ApplicationContract
   {
      putenv("PHPSPA_COMPRESSOR_LIB=$path");
      return $this;
   }

   public function setOxcLibraryPath(string $path): ApplicationContract
   {
      putenv("PHPSPA_OXC_LIB=$path");
      return $this;
   }

   public function forceNativeCompression(): ApplicationContract
   {
      // "Native" here means the native (FFI) compression path — C++, esbuild,
      // or Oxc — must NOT fall back to the PHP minifier. If the chosen native
      // engine fails/unavailable, throw instead of silently degrading to PHP.
      // It does NOT change which JS engine is selected; Oxc/esbuild opt-ins stay.
      putenv("PHPSPA_COMPRESSION_STRATEGY=native");
      return $this;
   }

   public function skipPreloadCheck(): ApplicationContract
   {
      // Skip the `FFI::scope()` preload probe in both native compressors and go
      // straight to the dynamic `FFI::cdef` load. The resolved handle is cached
      // in a static property, so under a persistent runtime (FrankenPHP) only the
      // first request per worker pays the cdef cost; later concurrent calls reuse
      // the already-loaded FFI instance without re-running cdef.
      putenv('PHPSPA_SKIP_PRELOAD_CHECK=1');
      return $this;
   }

   public function script (callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []): ApplicationContract
   {
      $scripts = [
         'content' => $content,
         'name' => $name,
         'type' => $type
      ];

      foreach ($attributes as $attribute => $value) {
         if ((!\is_string($attribute) || !\is_string($value)) && $value !== true) continue;
         $scripts[$attribute] = $value;
      }

      $this->scripts[] = $scripts;
      return $this;
   }

   public function styleSheet (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel='stylesheet', array $attributes = []): ApplicationContract
   {
      $this->link($content, $name, $type, $rel, $attributes);
      return $this;
   }

   public function link (callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []): ApplicationContract
   {
      $stylesheets = [
         'content' => $content,
         'name' => $name,
         'type' => $type,
         'rel' => $rel,
      ];

      foreach ($attributes as $attribute => $value) {
         if ((!\is_string($attribute) || !\is_string($value)) && $value !== true) continue;
         $stylesheets[$attribute] = $value;
      }

      $this->stylesheets[] = $stylesheets;
      return $this;
   }

   public function meta(
      ?string $name = null,
      ?string $content = null,
      ?string $property = null,
      ?string $httpEquiv = null,
      ?string $charset = null,
      array $attributes = []
   ): ApplicationContract {
      $entry = [];

      if ($name !== null) {
         $entry['name'] = $name;
      }

      if ($property !== null) {
         $entry['property'] = $property;
      }

      if ($httpEquiv !== null) {
         $entry['http-equiv'] = $httpEquiv;
      }

      if ($charset !== null) {
         $entry['charset'] = $charset;
      }

      if ($content !== null) {
         $entry['content'] = $content;
      }

      foreach ($attributes as $attribute => $value) {
         if (!\is_string($attribute) || $value === null || $value === '') {
            continue;
         }
         $entry[$attribute] = $value;
      }

      if (empty($entry)) {
         return $this;
      }

      if (!isset($entry['content']) && !isset($entry['charset'])) {
         return $this;
      }

      $this->metadata[] = $entry;

      return $this;
   }

   public function useStatic(string $route, string $staticPath): ApplicationContract {
      $this->static[] = ['route' => $route, 'staticPath' => $staticPath]; return $this;
   }

   public function prefix(string $path, callable|string $handler): ApplicationContract {
      $this->prefix[] = ['path' => $path, 'handler' => $handler]; return $this;
   }

   public function useModule(): ApplicationContract {
      $this->module = true; return $this;
   }

   public function useEsbuildForJsMinification(): ApplicationContract {
      $this->useEsbuild = true; return $this;
   }

   /**
    * @deprecated Since v2.1.0. No-op — esbuild is no longer the default engine, so
    *             there is nothing to disable. Opt INTO esbuild with
    *             {@see useEsbuildForJsMinification()} instead. Will be removed in a
    *             future version.
    */
   public function disableMinificationWithEsbuild(): ApplicationContract {
      // Intentionally does nothing. Back when esbuild was the default JS engine
      // this turned it off; now C++ is the default (esbuild is opt-in), so the
      // "disabled" state is already the default and this call has no effect.
      return $this;
   }

   public function useOxcForJsMinification(): ApplicationContract {
      $this->useOxc = true; return $this;
   }

   public function disableLogs(int $flags = DISABLE_COMPRESSION_LOG | DISABLE_LIBRARY_LOG | DISABLE_PRELOAD_LOG): ApplicationContract {
      $this->disableLogs |= $flags; return $this;
   }

   public function randomizeAssetName(): ApplicationContract {
      $this->randomizeAssetName = true; return $this;
   }

   public function combineAssetsPerComponent(): ApplicationContract {
      $this->combineAssets = true;
      $this->randomizeAssetName = true; // Combined bundles have no single name
      return $this;
   }

   public function setGeneratedCacheDirectory(string $path): ApplicationContract {
      $this->generatedCacheDirectory = $path; return $this;
   }

   public function run (bool $return = false)
   {
      HttpRequest::$cachedRawPostData = null;
      HttpRequest::$cachedPostData = null;

      $request = new HttpRequest();
      static::$request_uri = $request->path();
      $this->renderedData = null;

      $staticFileOutput = $this->resolveStaticPath($return);
      if (!empty($staticFileOutput)) return $staticFileOutput;

      $this->resolveCors($request);
      $assetResponse = $this->handlePhpSPARequest($request, $return);

      if (isset($assetResponse)) return $assetResponse;

      /**
       * Handle asset requests (CSS/JS files from session-based links)
       */
      // Auto-detect and set base path for proper asset URL resolution
      PathResolver::autoDetectBasePath();

      // #2 -- Decode + HMAC-verify the asset URL ONCE per request and reuse the
      // result for every App, instead of re-validating the same link each time.
      $assetInfo = self::resolveAssetRequestMemoized(static::$request_uri);

      if ($assetInfo !== null) {
         // #3 -- Fast skip: an instance-scoped global asset (`__global__:<id>`)
         // owned by a DIFFERENT App is not ours; do nothing and let the owning
         // App's run() handle it, without entering serveAsset at all.
         if ($this->globalAssetOwnership($assetInfo['componentRoute']) === -1) {
            if ($return) return null;
            return;
         }

         $assetOutput = $this->serveAsset($assetInfo, $return);
         if ($return) return $assetOutput;
         return;
      }

      // --- Reset DOM/Nonce/Reset HttpRequest static state for worker mode compatibility ---
      DOM::reset();
      Nonce::reset();
      Nonce::sendHeader();
      $success = false;

      foreach ($this->prefix as $prefix) {
         $output = $this->handlePrefix($prefix, [], $return);
         if (isset($output)) return $output;
      }

      if (empty($this->components) && empty($this->prefix)) {
         // --- If component is empty, then create an empty component ---
         $this->attach(new Component(fn() => ''));
      }

      foreach ($this->components as $component) {
         $output = $this->runComponent($component, false, $this->renderedData);

         if ($output === true) {
            $success = true;
            break;
         }
      }

      if ($success === true) {
         $timer = new Timer();

         $timer->start();
         $compressedOutput = Compressor::compress((string) $this->renderedData, 'text/html');
         if (Compressor::getLevel() !== Compressor::LEVEL_NONE && !($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
            error_log("Compressed HTML output within {$timer->getFormattedElapsedTime()} | From {$request->path()}");
         }

         if ($return) return $compressedOutput;

         print_r($compressedOutput);
         exit(0);
      }

   }

   private function resolveCors(Request $request) {
      if (!headers_sent()) {
         foreach ($this->cors as $key => $value) {
            $key =
               'Access-Control-' . str_replace('_', '-', ucwords($key, '_'));
            $value = \is_array($value) ? implode(', ', $value) : $value;

            $header_value =
               $key .
               ': ' .
               (\is_bool($value) ? var_export($value, true) : $value);
            header($header_value);
         }
      }

      /**
       * Handle preflight requests (OPTIONS method)
       */
      if ($request->isMethod('options')) {
         exit();
      }
   }

   private function handlePhpSPARequest(Request $request, bool $return) {
      if ($request->requestedWith() === 'PHPSPA_REQUEST' && $request->isSameOrigin()) {
         $data = json_decode(base64_decode($request->auth()->bearer ?? ''), true);

         if (isset($data['state'])) {
            $state = $data['state'];

            if (isset($state['key'])) {
               $sessionData = SessionHandler::get(STATE_HANDLE);
               $sessionData[$state['key']] = @$state['value'];
               SessionHandler::set(STATE_HANDLE, $sessionData);
            }

            return;

         
         } else if (isset($data['__call'])) {
            try {
               $raw = base64_decode($data['__call']['token'] ?? '');

               /**
                * @var array{0: string, 1: string, 2: bool} $tokenData
                */
               $payload = ($raw !== false && $raw !== '') ? @unserialize($raw) : null;

               $publicName = $payload[0] ?? null;
               $token = $payload[1] ?? null;
               $use_once = $payload[2] ?? false;
               
               if (!$publicName || !$token) {
                  throw new \Exception('Invalid or Expired Token');
               }

               $csrf = new CsrfManager($publicName, null, CALL_FUNC_HANDLE);

               if ($functionName = $csrf->verifyToken($token, $use_once)) {
                  // Convert ANY callable into a single Closure object
                  $closure = ($functionName instanceof \Closure) ? $functionName : \Closure::fromCallable($functionName);
                  $func = new ReflectionFunction($closure);
                  $totalRequiredParams = $func->getNumberOfRequiredParameters();
                  $totalParams = $func->getNumberOfParameters();

                  $args = $data['__call']['args'] ?? [];

                  $totalReceivedArgs = \count($args);
                  $validatedParams = [];

                  if ($totalReceivedArgs < $totalRequiredParams) throw new \InvalidArgumentException('Missing required arguments');
                  if ($totalReceivedArgs > $totalParams) throw new \InvalidArgumentException('Too many arguments');

                  foreach ($func->getParameters() as $param) {
                     $paramName = $param->getName();
                     $paramTypes = CallableInspector::getParamTypes($param);

                     if (!array_key_exists($paramName, $args)) {
                        if ($param->isDefaultValueAvailable()) {
                           $validatedParams[$paramName] = $param->getDefaultValue();
                           continue;
                        }
                        throw new \InvalidArgumentException("Missing required parameter: {$paramName}");
                     }

                     $argsValue = Validate::validate($args[$paramName] ?? null);
                     if (!Validate::matchesType($argsValue, $paramTypes)) {
                        throw new \InvalidArgumentException("Invalid type for parameter: {$paramName}");
                     }

                     $validatedParams[$paramName] = $argsValue;
                  }

                  $extraKeys = \array_diff_key($args, $validatedParams);

                  if (!empty($extraKeys)) {
                     $unknown = \implode(', ', \array_keys($extraKeys));
                     throw new \InvalidArgumentException("Received unexpected arguments: {$unknown}");
                  }

                  $response = \call_user_func($closure, ...$validatedParams);

                  if ($response instanceof Response) {
                     $response = $response->__toString();
                  } elseif (\is_array($response) || \is_object($response)) {
                     if (!\headers_sent()) header('Content-Type: application/json');
                     $response = json_encode($response);
                  }

                  if ($return) return $response;
                  echo $response;
                  exit(0);
               }
               else {
                  throw new \Exception('Invalid or Expired Token');
               }
            } catch (\Exception $e) {
               error_log($e->getMessage());
               if ($return) return $e->getMessage();
               echo $e->getMessage();
               exit(0);
            }
         }
      }
   }


   private function runComponent(Component|Icomponent $component, bool $isPreloadingComponent = false, ?string &$layoutOutput = null) {
      static $request = new HttpRequest();
      static $router = null;

      $route = CallableInspector::getProperty($component, 'route');
      $name = CallableInspector::getProperty($component, 'name');
      $pattern = CallableInspector::getProperty($component, 'pattern');
      $method = CallableInspector::getProperty($component, 'method') ?? 'GET|VIEW';
      $caseSensitive = CallableInspector::getProperty($component, 'caseSensitive') ?? $this->defaultCaseSensitive;
      $targetID = CallableInspector::getProperty($component, 'targetID') ?? $this->defaultTargetID;
      $scripts = CallableInspector::getProperty($component, 'scripts');
      $stylesheets = CallableInspector::getProperty($component, 'stylesheets');
      $componentMetaData = CallableInspector::getProperty($component, 'metadata');
      $componentFunction = CallableInspector::getProperty($component, 'component');
      $title = CallableInspector::getProperty($component, 'title');
      $reloadTime = CallableInspector::getProperty($component, 'reloadTime');
      $exact = CallableInspector::getProperty($component, 'exact') ?? false;
      $preload = CallableInspector::getProperty($component, 'preload');
      $componentMiddlewares = CallableInspector::getProperty($component, 'middlewares');

      if (!$componentFunction || !is_callable($componentFunction)) {
         return;
      }

      if (!$route && !$isPreloadingComponent) {
         $m = explode('|', $method);
         if (!\in_array($request->method(), $m)) return;
      } else if (!$isPreloadingComponent) {
         $router = (new MapRoute($method, $route, $caseSensitive, $pattern))->match();

         if (!$router)
            return; // --- Skip if no match found ---

         $request = new HttpRequest($router['params'] ?? []);

         DOM::CurrentRoutes(static::$request_uri);

         if ($request->requestedWith() !== 'PHPSPA_REQUEST' && $request->isSameOrigin()) {
            Session::remove(STATE_HANDLE);
         }
      }
      
      if ($isPreloadingComponent && !str_contains($route[0] ?? '', '{')) {
         DOM::CurrentRoutes($route[0] ?? '');
      }

      if ($name) DOM::CurrentComponents($name);

      // --- Merge meta: component meta overrides app meta, DOM meta overrides both ---
      $metaTags = $this->metadata;
      // Remove app meta that is overridden by component meta
      foreach ($componentMetaData as $compEntry) {
         foreach ($metaTags as $k => $meta) {
            if ((isset($compEntry['name']) && isset($meta['name']) && $compEntry['name'] === $meta['name']) ||
                (isset($compEntry['property']) && isset($meta['property']) && $compEntry['property'] === $meta['property']) ||
                (isset($compEntry['charset']) && isset($meta['charset']) && $compEntry['charset'] === $meta['charset'])) {
               unset($metaTags[$k]);
            }
         }
      }
      $metaTags = \array_merge($metaTags, $componentMetaData);

      $middlewares = [...$this->middlewares, ...$componentMiddlewares];

      // --- Define the final handler that executes the component function ---
      $finalHandler = function () use ($componentFunction, $router, &$request) {
         // --- Clear component scope before each component execution ---
         ComponentScope::clearAll();

         // --- Execute the component function and format its output ---
         $componentOutput = $this->executeComponentFunction($request, $componentFunction, $router['params'] ?? []);

         $scopeId = ComponentScope::createScope();
         $componentOutput = static::format($componentOutput);
         ComponentScope::removeScope($scopeId);

         return $componentOutput;
      };

      // --- Build the middleware pipeline ---
      // --- Reverse middlewares so the first middleware in the array is executed first ---
      $pipeline = array_reduce(
         array_reverse($middlewares),
         function (callable $next, callable $middleware) use (&$request) {
            return fn () => $middleware($request, $next);
         },
         $finalHandler
      );

      // --- Execute the pipeline ---
      $componentOutput = $pipeline($request);

      if ($layoutOutput === null) {
         $layoutOutput = is_callable($this->layout) ? (string) \call_user_func($this->layout, $request) : (string) $this->layout;
         $layoutOutput = $this->ensureHeadTag($layoutOutput);
      }

      // --- Check if a preload component exists ---
      // --- Then first parse & execute preload components ---
      if (!$isPreloadingComponent && isset($preload[0]) && isset($this->components[$preload[0]]) && $request->requestedWith() !== 'PHPSPA_REQUEST') {
         foreach ($preload as $componentKey) {
            $preloadComponent = $this->components[$componentKey];

            $this->runComponent(
               component: $preloadComponent,
               isPreloadingComponent: true,
               layoutOutput: $layoutOutput
            );
         }
      }

      if (!$isPreloadingComponent) {
         $title = DOM::Title() ?? $title;
         DOM::Title($title);

         // Merge DOM meta (dynamic, set by user in component) and override any with same name/property/charset
         $domMeta = DOM::meta();

         if (!empty($domMeta)) {
            foreach ($domMeta as $domEntry) {
               foreach ($metaTags as $k => $meta) {
                  if ((isset($domEntry['name']) && isset($meta['name']) && $domEntry['name'] === $meta['name']) ||
                      (isset($domEntry['property']) && isset($meta['property']) && $domEntry['property'] === $meta['property']) ||
                      (isset($domEntry['charset']) && isset($meta['charset']) && $domEntry['charset'] === $meta['charset'])) {
                     unset($metaTags[$k]);
                  }
               }
            }
            $metaTags = [...$metaTags, ...$domMeta];
         }

         // Merge DOM link (dynamic, set by user in component) and override any with same name or rel
         $domLinks = DOM::link();

         if (!empty($domLinks)) {
            foreach ($domLinks as $domLink) {
               $domName = $domLink['name'] ?? null;
               $domHref = \is_string($domLink['content']) ? $domLink['content'] : null;
               $domRel = $domLink['rel'] ?? null;
               $replaced = false;

               foreach ($stylesheets as $k => $link) {
                  // Override by name (preserve position)
                  if ($domName !== null && isset($link['name']) && $link['name'] === $domName) {
                     $stylesheets[$k] = $domLink;
                     $replaced = true;
                     break;
                  }

                  // Override by rel (preserve position)
                  if ($domRel !== null && isset($link['rel']) && $link['rel'] === $domRel) {
                     $stylesheets[$k] = $domLink;
                     $replaced = true;
                     break;
                  }
               }

               // Also check global stylesheets
               foreach ($this->stylesheets as $k => $link) {
                  // Override by name
                  if ($domName !== null && isset($link['name']) && $link['name'] === $domName) {
                     $this->stylesheets[$k] = $domLink;
                     $replaced = true;
                     break;
                  }

                  // Override by rel
                  if ($domRel !== null && isset($link['rel']) && $link['rel'] === $domRel) {
                     $this->stylesheets[$k] = $domLink;
                     $replaced = true;
                     break;
                  }
               }

               // Append only if no existing link was replaced
               if (!$replaced) {
                  $stylesheets[] = $domLink;
               }
            }
         }

         if ($request->requestedWith() !== 'PHPSPA_REQUEST' && !empty($metaTags)) {
            $metaMarkup = $this->buildMetaTagMarkup($metaTags);

            if ($metaMarkup !== '') {
               $layoutOutput = $this->injectMetaTags($layoutOutput, $metaMarkup);
            }
         }
      }

      // --- Generate session-based links for scripts and stylesheets instead of inline content ---
      $assetLinks = $this->generateAssetLinks($route, $scripts, $stylesheets, $this->scripts, $this->stylesheets);

      if ($request->requestedWith() === 'PHPSPA_REQUEST') {
         // --- For PHPSPA requests (component updates), include component scripts with the component ---
         $componentOutput = $assetLinks['component']['stylesheets'] . $componentOutput . $assetLinks['global']['scripts'] . $assetLinks['component']['scripts'];

         /**
          * @var array{
          *    content: string,
          *    stateData: array,
          *    title: string,
          *    targetID: mixed,
          *    reloadTime: int,
          *    exact: bool,
          * }
          */
         $info = [
            'content' => Compressor::compressComponent($componentOutput, $this->disableLogs),
            'stateData' => SessionHandler::get(STATE_HANDLE),
            'title' => $title,
            'targetID' => $targetID,
            'exact' => $exact,
         ];

         if (@((int) $reloadTime) > 0)
            $info['reloadTime'] = $reloadTime;

         // Use compressed JSON output
         print_r(Compressor::compressJson($info));
         exit(0);
      }
      else {
         return $this->MainDOMOutput(
            isPreloadingComponent: $isPreloadingComponent,
            assetLinks: $assetLinks,
            layoutOutput: $layoutOutput,
            componentOutput: $componentOutput ?? '',
            targetID: $targetID,
            reloadTime: $reloadTime,
            title: $title,
            exact: $exact
         );
      }
   }


   private function MainDOMOutput(bool $isPreloadingComponent, array $assetLinks, string &$layoutOutput, string $componentOutput, string $targetID, $reloadTime, $title, $exact) {
      // --- For regular HTML requests, only include component stylesheets with the component content ---
      // --- Component scripts will be injected later to ensure proper execution order ---
      $componentOutput = $assetLinks['component']['stylesheets'] . $componentOutput;

      // --- Initialize static variable once ---
      /**
       * Target information for each components (main & preload)
       * 
       * @var array{
       *    targetIDs: string[],
       *    currentRoutes: string[],
       *    defaultContent: string[],
       *    exact: bool[],
       * }
       * @static
       */
      static $targetInformation = [];

      // --- This remain static for all components ---
      static $isFirstComponent = null;

      // --- Reset static state between worker requests ---
      static $lastRequestTime = null;
      $currentRequestTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
      if ($currentRequestTime !== null && $lastRequestTime !== $currentRequestTime) {
         $targetInformation = [];
         $isFirstComponent = null;
         $lastRequestTime = $currentRequestTime;
      }

      if (!\is_array($targetInformation)) {
         $targetInformation = [];
      }

      $isFirstComponent = $isFirstComponent === null ? true : false;

      if (!$isPreloadingComponent) {
         if ($title) {
            $count = 0;

            $layoutOutput = preg_replace_callback(
               '/(<head\b[^>]*>)(.*?)(<\/head>)/is',
               function ($headMatches) use ($title, &$count) {
                  $headContent = preg_replace_callback(
                     '/<title\b([^>]*)>.*?<\/title>/si',
                     fn ($matches) => "\n      <title" . ($matches[1] ?? null) . '>' . $title . '</title>',
                     $headMatches[2],
                     1,
                     $count
                  );
                  return $headMatches[1] . $headContent . $headMatches[3];
               },
               $layoutOutput,
               1
            );

            if ($count === 0) {
               // --- If no <title> tag was found, add one inside the <head> section ---
               $layoutOutput = preg_replace(
                  '/<head\b([^>]*)>/i',
                  "<head$1>\n      <title>$title</title>",
                  $layoutOutput,
                  1,
               );
            }
         }

         $tt = ' data-phpspa-target';
         $layoutOutput = static::format($layoutOutput) ?? '';
      }
         
      $tag = '/<(\w+)([^>]*id\s*=\s*["\']?' . preg_quote($targetID, '/') . '["\']?[^>]*)>(.*?)<\/\1>/si';

      $tt ??=  "";

      // --- This render the component to the target ID ---
      $this->renderedData = preg_replace_callback(
         $tag,
         function ($matches) use (&$targetInformation, &$tt, $componentOutput, $isPreloadingComponent, $exact, $reloadTime, $targetID) {
            // --- $matches[1] contains the tag name, ---
            // --- $matches[2] contains attributes with the target ID, ---
            // --- $matches[3] contains the default content inside the tag ---


            // --- Update on main component and preloading components ---
            $targetInformation['currentRoutes'] = DOM::CurrentRoutes();
            $targetInformation['exact'][] = $exact;
            $targetInformation['targetIDs'][] = $targetID;
            $targetInformation['defaultContent'][] = Compressor::compressComponent($matches[3], $this->disableLogs);

            // --- This is the last component which is the main component ---
            // --- Then attach it directly to the component target element ---
            if (!$isPreloadingComponent) {
               $targetInformation['stateData'] = SessionHandler::get(STATE_HANDLE);

               if ($reloadTime > 0) {
                  $targetInformation['reloadTime'] = $reloadTime;
               }

               $targetInformation = base64_encode(json_encode($targetInformation));
               $tt .= " phpspa-target-data=\"$targetInformation\"";
            }

            return '<' . $matches[1] . $matches[2] . "$tt>" .
               $componentOutput .
            '</' . $matches[1] . '>';
         },
         $layoutOutput,
      );

      if ($isFirstComponent) {
         // --- Inject global assets at the end of body tag (or html tag if no body exists) ---
         // --- Also inject component scripts after global scripts for proper execution order ---
         $this->renderedData = $this->injectGlobalAssets($this->renderedData, $assetLinks['global'], $assetLinks['component']['scripts']);
      } else {
         $this->renderedData = $this->injectGlobalAssets($this->renderedData, $assetLinks['component']['scripts']);
      }

      // --- This is the end of component, which is the main component after all preloading component, return true ---
      if (!$isPreloadingComponent) {
         return true;
      }
   }

   private function buildMetaTagMarkup(array $metaEntries): string
   {
      $lines = [];

      foreach ($metaEntries as $entry) {
         if (!\is_array($entry)) {
            continue;
         }

         $attributes = Validate::validate($entry);
         $attributes = HTMLAttrInArrayToString($attributes);

         if (empty($attributes)) {
            continue;
         }

         $signature = $this->metaSignature($entry);
         $lines[$signature] = "<meta $attributes />";
      }

      if (empty($lines)) {
         return '';
      }

      return implode("\n      ", array_values($lines));
   }

   private function injectMetaTags(string $layoutOutput, string $metaMarkup): string
   {
      $metaBlock = '      ' . $metaMarkup . "\n";

      $updated = preg_replace('/<head\b([^>]*)>/', "<head$1>\n$metaBlock", $layoutOutput, 1, $count);

      if ($count > 0 && \is_string($updated)) {
         return $updated;
      }

      return $layoutOutput;
   }

   private function metaSignature(array $entry): string
   {
      ksort($entry);
      return md5(json_encode($entry));
   }

   private function ensureHeadTag(string $layoutOutput): string
   {
      if (preg_match('/<head\b[^>]*>/i', $layoutOutput)) {
         return $layoutOutput;
      }

      $headMarkup = "<head></head>\n";

      if (preg_match('/<body\b[^>]*>/i', $layoutOutput, $matches, PREG_OFFSET_CAPTURE)) {
         $pos = $matches[0][1];
         return substr($layoutOutput, 0, $pos) . $headMarkup . substr($layoutOutput, $pos);
      }

      if (preg_match('/<html\b[^>]*>/i', $layoutOutput, $matches, PREG_OFFSET_CAPTURE)) {
         $pos = $matches[0][1] + \strlen($matches[0][0]);
         return substr($layoutOutput, 0, $pos) . "\n" . $headMarkup . substr($layoutOutput, $pos);
      }

      return $headMarkup . $layoutOutput;
   }

   /**
    * Generate session-based links for component assets
    *
    * @param array|string $route Component route
    * @param array $scripts Array of component script callables
    * @param array $stylesheets Array of component stylesheet callables
    * @param array $globalScripts Array of global script callables
    * @param array $globalStylesheets Array of global stylesheet callables
    * @return array Array with 'component' and 'global' sections, each containing 'scripts' and 'stylesheets' HTML
    */
   private function generateAssetLinks ($route, array $scripts, array $stylesheets, array $globalScripts = [], array $globalStylesheets = []): array
   {
      $request = new HttpRequest();
      $isPhpSpaRequest = $request->requestedWith() === 'PHPSPA_REQUEST' || $request->requestedWith() === 'PHPSPA_REQUEST_SCRIPT';

      $result = [
         'component' => [ 'scripts' => '', 'stylesheets' => '' ],
         'global' => [ 'scripts' => '', 'stylesheets' => '' ]
      ];

      // --- Get the primary route for mapping purposes ---
      $primaryRoute = \is_array($route) ? $route[0] ?? null : $route;
      
            // --- Empty / match-all routes have no route string; use a stable sentinel so
            // --- asset links (and their cache keys) are valid and round-trip back to the
            // --- correct component via findComponentByRoute(). Without this, a null route
            // --- hits generateCssLink()/generateJsLink()'s non-nullable string param. ---
            if ($primaryRoute === null || $primaryRoute === '') {
               $primaryRoute = '__root__';
            }

      // --- Scoped global route: `__global__:<appId>` binds a global asset link to
      // --- THIS App instance, so a second `new App()` with its own global callables
      // --- can't have its global served by the first App (or vice versa). The bare
      // --- `__global__` is reserved for framework-owned assets (the phpspa SPA
      // --- script at index -1), which every App serves identically. ---
      $globalRoute = '__global__:' . $this->globalScopeId();

      // ==================== COMBINED ASSETS PROTOTYPE ====================
      if ($this->combineAssets) {
         return $this->generateCombinedAssetLinks($primaryRoute, $globalRoute, $scripts, $stylesheets, $globalScripts, $globalStylesheets, $isPhpSpaRequest);
      }
      // ==================== END COMBINED ASSETS PROTOTYPE ====================

      // --- Generate global stylesheet links ---
      if (!empty($globalStylesheets) && !$isPhpSpaRequest) {
         foreach ($globalStylesheets as $index => $stylesheet) {
            $stylesheet = (array) Validate::validate($stylesheet);

            if (is_callable($stylesheet['content'])) {
               $modificationTime = new FileFunction($stylesheet['content'])->getFileModificationTime();
               $stylesheet['href'] = AssetLinkManager::generateCssLink($globalRoute, $index, $modificationTime, $this->randomizeAssetName ? null : ($stylesheet['name'] ?? null));
            } else
               $stylesheet['href'] = $stylesheet['content'];

            unset($stylesheet['name']);
            unset($stylesheet['content']);

            // --- Preload hint disabled: styles already sit at the top of <head>,
            // --- so the browser discovers them nearly as early without the extra tag.
            // --- Revisit with HTTP `Link:` / 103 Early Hints in a future release.
            // if ($stylesheet['rel'] === 'stylesheet') {
            //    $preloadStylesheet = $stylesheet;
            //
            //    $preloadStylesheet['rel'] = 'preload';
            //    $preloadStylesheet['as'] = 'style';
            //    $attributes = HTMLAttrInArrayToString($preloadStylesheet);
            //    $result['global']['stylesheets'] .= "\n      <link $attributes />";
            // }

            $attributes = HTMLAttrInArrayToString($stylesheet);
            $result['global']['stylesheets'] .= "\n      <link $attributes />";
         }
      }

      // --- Generate component stylesheet links ---
      if (!empty($stylesheets)) {
         foreach ($stylesheets as $index => $stylesheet) {
            $stylesheet = (array) Validate::validate($stylesheet);

            if (\is_callable($stylesheet['content'])) {
               $modificationTime = new FileFunction($stylesheet['content'])->getFileModificationTime();
               $stylesheet['href'] = AssetLinkManager::generateCssLink($primaryRoute, $index, $modificationTime, $this->randomizeAssetName ? null : ($stylesheet['name'] ?? null));
            } else
               $stylesheet['href'] = $stylesheet['content'];

            unset($stylesheet['name']);
            unset($stylesheet['content']);

            // --- Preload hint disabled: styles already sit at the top of <head>,
            // --- so the browser discovers them nearly as early without the extra tag.
            // --- Revisit with HTTP `Link:` / 103 Early Hints in a future release.
            // if ($stylesheet['rel'] === 'stylesheet' && !$isPhpSpaRequest) {
            //    $preloadStylesheet = $stylesheet;
            //
            //    unset($preloadStylesheet['name']);
            //    unset($preloadStylesheet['content']);
            //
            //    $preloadStylesheet['rel'] = 'preload';
            //    $preloadStylesheet['as'] = 'style';
            //    $attributes = HTMLAttrInArrayToString($preloadStylesheet);
            //    $result['component']['stylesheets'] .= "\n      <link $attributes />";
            // }

            $attributes = HTMLAttrInArrayToString($stylesheet);
            $result['component']['stylesheets'] .= "\n      <link $attributes />";

         }
      }

      // --- Automatically add phpspa script for SPA functionality ---
      // --- Attaching it to the global stylesheet to expicitly... ---
      // --- add it to the head tag alongside with the styles instead of the body ---
      if (!$isPhpSpaRequest && !$this->module) {
         $modificationTime = filemtime($this->getPhpSPAScriptPath());
         $jsLink = AssetLinkManager::generateJsLink("__global__", -1, $modificationTime);
         $result['global']['stylesheets'] .= "\n      <script type=\"text/javascript\" src=\"$jsLink\"></script>\n";
      }

      // --- Generate global script links ---
      if (!empty($globalScripts) && !$isPhpSpaRequest) {
         foreach ($globalScripts as $index => $script) {
            $script = (array) Validate::validate($script);
            $isLink = false;

            if (is_callable($script['content'])) {
               $modificationTime = new FileFunction($script['content'])->getFileModificationTime();
               $script['src'] = AssetLinkManager::generateJsLink($globalRoute, $index, $modificationTime, $this->randomizeAssetName ? null : ($script['name'] ?? null), $script['type']);
            } else {
               $script['src'] = $script['content'];
               $isLink = true;
            }

            unset($script['name']);
            unset($script['content']);
            $attributes = HTMLAttrInArrayToString($script);

            $result['global']['scripts'] .= "\n      <script $attributes></script>";
         }
      }

      // --- Generate component script links ---
      if (!empty($scripts)) {
         foreach ($scripts as $index => $script) {
            $script = (array) Validate::validate($script);
            $isLink = false;

            if (is_callable($script['content'])) {
               $modificationTime = new FileFunction($script['content'])->getFileModificationTime();
               $script['src'] = AssetLinkManager::generateJsLink($primaryRoute, $index, $modificationTime, $this->randomizeAssetName ? null : ($script['name'] ?? null), $script['type']);
            } else {
               $script['src'] = $script['content'];
               $isLink = true;
            }

            unset($script['name']);
            unset($script['content']);
            $attributes = HTMLAttrInArrayToString($script);

            $result['component']['scripts'] .= $isPhpSpaRequest && !$isLink
               ? "\n      <script data-type=\"phpspa/script\" $attributes></script>"
               : "\n      <script $attributes></script>";
         }
      }

      return $result;
   }

   /**
    * Generate combined asset links (prototype for combineAssetsPerComponent)
    *
    * Groups callable assets by (scope, type) and generates one combined link per group.
    * External links (non-callables) are emitted individually.
    *
    * @param string|null $primaryRoute Component route
    * @param array $scripts Component scripts
    * @param array $stylesheets Component stylesheets
    * @param array $globalScripts Global scripts
    * @param array $globalStylesheets Global stylesheets
    * @param bool $isPhpSpaRequest Whether this is an SPA request
    * @return array Asset links result
    */
   private function generateCombinedAssetLinks (?string $primaryRoute, string $globalRoute, array $scripts, array $stylesheets, array $globalScripts, array $globalStylesheets, bool $isPhpSpaRequest): array
   {
      $result = [
         'component' => [ 'scripts' => '', 'stylesheets' => '' ],
         'global' => [ 'scripts' => '', 'stylesheets' => '' ]
      ];

      // --- Helper to group callables by type and collect external links ---
      $groupAssets = function (array $assets, string $typeKey = 'type') {
         $callableGroups = []; // grouped by script/rel type
         $externalLinks = [];
         $mtimes = [];

         foreach ($assets as $index => $asset) {
            $asset = (array) Validate::validate($asset);

            if (\is_callable($asset['content'])) {
               $assetType = $asset[$typeKey] ?? 'default';
               if (!isset($callableGroups[$assetType])) {
                  $callableGroups[$assetType] = ['indexes' => [], 'mtimes' => [], 'attrs' => $asset];
               }
               $callableGroups[$assetType]['indexes'][] = $index;
               $callableGroups[$assetType]['mtimes'][] = (new FileFunction($asset['content']))->getFileModificationTime();
            } else {
               $externalLinks[] = $asset;
            }
         }

         return ['groups' => $callableGroups, 'external' => $externalLinks];
      };

      // --- Generate global stylesheet links (combined) ---
      if (!empty($globalStylesheets) && !$isPhpSpaRequest) {
         $grouped = $groupAssets($globalStylesheets, 'rel');

         // Combined callable groups
         foreach ($grouped['groups'] as $rel => $group) {
            $maxMtime = \max($group['mtimes']);
            $href = AssetLinkManager::generateCombinedCssLink($globalRoute, $group['indexes'], $maxMtime, $group['attrs']['type'] ?? 'text/css');

            $attrs = $group['attrs'];
            $attrs['href'] = $href;
            unset($attrs['name'], $attrs['content']);

            // --- Preload hint disabled: styles already sit at the top of <head>,
            // --- so the browser discovers them nearly as early without the extra tag.
            // --- Revisit with HTTP `Link:` / 103 Early Hints in a future release.
            // if (($attrs['rel'] ?? 'stylesheet') === 'stylesheet') {
            //    $preload = $attrs;
            //    $preload['rel'] = 'preload';
            //    $preload['as'] = 'style';
            //    $result['global']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($preload) . " />";
            // }
            $result['global']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($attrs) . " />";
         }

         // External links (non-callable)
         foreach ($grouped['external'] as $ext) {
            $ext['href'] = $ext['content'];
            unset($ext['name'], $ext['content']);
            $result['global']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($ext) . " />";
         }
      }

      // --- Generate component stylesheet links (combined) ---
      if (!empty($stylesheets)) {
         $grouped = $groupAssets($stylesheets, 'rel');

         foreach ($grouped['groups'] as $rel => $group) {
            $maxMtime = \max($group['mtimes']);
            $href = AssetLinkManager::generateCombinedCssLink($primaryRoute, $group['indexes'], $maxMtime, $group['attrs']['type'] ?? 'text/css');

            $attrs = $group['attrs'];
            $attrs['href'] = $href;
            unset($attrs['name'], $attrs['content']);

            // --- Preload hint disabled: styles already sit at the top of <head>,
            // --- so the browser discovers them nearly as early without the extra tag.
            // --- Revisit with HTTP `Link:` / 103 Early Hints in a future release.
            // if (($attrs['rel'] ?? 'stylesheet') === 'stylesheet' && !$isPhpSpaRequest) {
            //    $preload = $attrs;
            //    $preload['rel'] = 'preload';
            //    $preload['as'] = 'style';
            //    $result['component']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($preload) . " />";
            // }
            $result['component']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($attrs) . " />";
         }

         foreach ($grouped['external'] as $ext) {
            $ext['href'] = $ext['content'];
            unset($ext['name'], $ext['content']);
            $result['component']['stylesheets'] .= "\n      <link " . HTMLAttrInArrayToString($ext) . " />";
         }
      }

      // --- Automatically add phpspa script for SPA functionality ---
      if (!$isPhpSpaRequest && !$this->module) {
         $modificationTime = filemtime($this->getPhpSPAScriptPath());
         $jsLink = AssetLinkManager::generateJsLink("__global__", -1, $modificationTime);
         $result['global']['stylesheets'] .= "\n      <script type=\"text/javascript\" src=\"$jsLink\"></script>\n";
      }

      // --- Generate global script links (combined) ---
      if (!empty($globalScripts) && !$isPhpSpaRequest) {
         $grouped = $groupAssets($globalScripts, 'type');

         // External scripts (e.g. CDN links) MUST always come first, before the
         // combined local bundle, so dependencies like jQuery load before the
         // callable scripts that rely on them.
         foreach ($grouped['external'] as $ext) {
            $ext['src'] = $ext['content'];
            unset($ext['name'], $ext['content']);
            $result['global']['scripts'] .= "\n      <script " . HTMLAttrInArrayToString($ext) . "></script>";
         }

         foreach ($grouped['groups'] as $type => $group) {
            $maxMtime = \max($group['mtimes']);
            $src = AssetLinkManager::generateCombinedJsLink($globalRoute, $group['indexes'], $maxMtime, $type);

            $attrs = $group['attrs'];
            $attrs['src'] = $src;
            unset($attrs['name'], $attrs['content']);
            $result['global']['scripts'] .= "\n      <script " . HTMLAttrInArrayToString($attrs) . "></script>";
         }
      }

      // --- Generate component script links (combined) ---
      if (!empty($scripts)) {
         $grouped = $groupAssets($scripts, 'type');

         // External scripts (e.g. CDN links) MUST always come first, before the
         // combined local bundle, so dependencies load before the callable
         // scripts that rely on them.
         foreach ($grouped['external'] as $ext) {
            $ext['src'] = $ext['content'];
            unset($ext['name'], $ext['content']);
            $result['component']['scripts'] .= "\n      <script " . HTMLAttrInArrayToString($ext) . "></script>";
         }

         foreach ($grouped['groups'] as $type => $group) {
            $maxMtime = \max($group['mtimes']);
            $src = AssetLinkManager::generateCombinedJsLink($primaryRoute, $group['indexes'], $maxMtime, $type);

            $attrs = $group['attrs'];
            $attrs['src'] = $src;
            unset($attrs['name'], $attrs['content']);

            $tag = $isPhpSpaRequest
               ? "\n      <script data-type=\"phpspa/script\" " . HTMLAttrInArrayToString($attrs) . "></script>"
               : "\n      <script " . HTMLAttrInArrayToString($attrs) . "></script>";
            $result['component']['scripts'] .= $tag;
         }
      }

      return $result;
   }

   /**
    * Serve CSS/JS asset content from session-based links
    *
    * @param array $assetInfo Asset information from AssetLinkManager
    */
   private function serveAsset (array $assetInfo, bool $return = false)
   {
      $request = new HttpRequest();
      $currentLevel = Compressor::getLevel();
      $ownership = $this->globalAssetOwnership($assetInfo['componentRoute']);

      // --- A global asset owned by a DIFFERENT App instance: do not serve it
      // --- here, let the request fall through to the owning app's run(). This
      // --- is what stops App A from serving App B's global callable script. ---
      if ($ownership === -1) {
         return;
      }

      $isGlobalAsset = $ownership === 1;
      $isPhpSpaRequest = $request->requestedWith() === 'PHPSPA_REQUEST' || $request->requestedWith() === 'PHPSPA_REQUEST_SCRIPT';
      $isJS = strtolower($assetInfo['assetType']) === 'js';

      // ==================== COMBINED ASSETS PROTOTYPE ====================
      if ($this->combineAssets && isset($assetInfo['assetIndexes']) && \is_array($assetInfo['assetIndexes'])) {
         return $this->serveCombinedAsset($assetInfo, $currentLevel, $isGlobalAsset, $isPhpSpaRequest, $isJS, $return);
      }
      // ==================== END COMBINED ASSETS PROTOTYPE ====================

      if ($isGlobalAsset) {
         $isScopedGlobal = \str_starts_with($assetInfo['componentRoute'], '__global__:');

         if ($assetInfo['assetType'] === 'css') {
            $entry = $this->stylesheets[$assetInfo['assetIndex']] ?? null;
            $callable = $entry['content'] ?? null;
         } else {
            $entry = $this->scripts[$assetInfo['assetIndex']] ?? null;
            $callable = $entry['content'] ?? null;
         }

         // --- Scope-id match is necessary but not sufficient. The instance id is
         // --- a monotonic per-process counter, so a removed App's id can be
         // --- reused by a later App. A stale cached (or CDN-held) `__global__:<id>`
         // --- URL would then resolve to a DIFFERENT App holding that id, which
         // --- would serve its OWN global at that index -- wrong bytes with a 200.
         // --- Guard it like a component asset is guarded by route: if the
         // --- scoped-global index has no entry on THIS app, the URL does not
         // --- belong here -> skip (404) instead of a mismatched serve. The bare
         // --- framework `__global__` (index -1, from the SPA script file) is
         // --- exempt, so $isScopedGlobal gates the check. ---
         if ($isScopedGlobal && $entry === null) {
            return;
         }
      } else {
         // --- Find the component that matches the asset's route ---
         $component = $this->findComponentByRoute($assetInfo['componentRoute']);

         if ($component === null) {
            return;
         }

         if ($assetInfo['assetType'] === 'css') {
            $stylesheets = CallableInspector::getProperty($component, 'stylesheets');
            $stylesheet = $stylesheets[$assetInfo['assetIndex']] ?? null;
            $callable = $stylesheet['content'] ?? null;
         } else {
            $scripts = CallableInspector::getProperty($component, 'scripts');
            $script = $scripts[$assetInfo['assetIndex']] ?? null;
            $callable = $script['content'] ?? null;
         }
      }

      if (\is_callable($callable)) {
         $ff = new FileFunction($callable);
         $fileName = $ff->getFileName();
         $fileModifyTime = $ff->getFileModificationTime();

         if ($fileName) {
            $hashed = md5($assetInfo['componentRoute'] . $assetInfo['assetIndex'] . $fileModifyTime . $assetInfo['assetType']);

            if (isset(static::$staticFilesCache[$hashed])) {
               $content = static::$staticFilesCache[$hashed];
               goto FINALIZE;
            }

            $fileDir = $this->generatedCacheDirectory ?? (pathinfo($fileName, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'generated');
            $newName = $fileDir . DIRECTORY_SEPARATOR . $hashed . ".generated.php";

            if (file_exists($newName)) {
               if ($currentLevel === Compressor::LEVEL_NONE) {
                  unlink($newName);
               }
               else {
                  $content = require $newName;
                  $content = Compressor::applyBinaryCompression($content);
                  // --- Save to cache ---
                  static::$staticFilesCache[$hashed] = $content;
                  goto FINALIZE;
               }
            }
         }
      }

      // --- Check if this is a global asset ---
      if ($isGlobalAsset) {
         $content = $this->getGlobalAssetContent($assetInfo, $callable);
      }
      else {
         if ($callable && is_callable($callable)) {
            $content = \call_user_func($callable);
         } else $content = '';
      }

      if ($content === null) {
         return;
      }

      // --- Determine compression level ---
      $compressionLevel = $isPhpSpaRequest ? Compressor::LEVEL_EXTREME : $currentLevel;

      if (\is_array($content)) {
         $content = $content[0];
      } else {
         if ($currentLevel !== Compressor::LEVEL_NONE) {
            $timer = new Timer();
            $assetType = strtoupper($assetInfo['assetType']);

            // --- Compress the content ---
            $timer->start();
            $content = $this->compressAssetContent($content, $compressionLevel, $assetInfo['type'], $isGlobalAsset ? 'global' : 'scoped');
            if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) error_log("Compressed $assetType asset output within {$timer->getFormattedElapsedTime()} | From {$assetInfo['name']}");

            if (!$isPhpSpaRequest && $fileName) {
               // Race-safe + recursive (parent 'generated' dir may not exist).
               if (!is_dir($fileDir) && !@mkdir($fileDir, 0755, true) && !is_dir($fileDir)) {
                  if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
                     error_log("phpspa: failed to create generated cache directory: $fileDir");
                  }
               }

               if (OxcCompressor::isErrorOutput($content) && !($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
                  error_log("phpspa: caching Oxc parse-error output for $assetType asset (will regenerate when the source changes): {$assetInfo['name']}");
               }
               if (!@file_put_contents($newName, "<?php\nreturn <<<'$assetType'\n$content\n$assetType;")) {
                  if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
                     error_log("phpspa: failed to cache asset: $newName");
                  }
               }

               // --- Set compression ---
               $content = Compressor::applyBinaryCompression($content);
               // --- Cache the content ---
               static::$staticFilesCache[$hashed] = $content;
            }
         } else {
            // --- Wrap component in IIFE ---
            if (!$isGlobalAsset && $isJS) {
               // --- Set compression ---
               $content = Compressor::applyBinaryCompression("(()=>{{$content}})()");
            }
         }
      }

      FINALIZE:

      // --- Set appropriate headers ---
      $this->setAssetHeaders($assetInfo['type']);
      // --- Output the content ---
      if ($return) return $content;
      echo $content;
      exit(0);
   }

   /**
    * Serve combined CSS/JS asset content (prototype for combineAssetsPerComponent)
    *
    * Loops through all indexes, concatenates content, compresses once, serves.
    *
    * @param array $assetInfo Asset information with assetIndexes array
    * @param int $currentLevel Current compression level
    * @param bool $isGlobalAsset Whether this is a global asset
    * @param bool $isPhpSpaRequest Whether this is an SPA request
    * @param bool $isJS Whether this is a JavaScript asset
    * @return void
    */
   private function serveCombinedAsset (array $assetInfo, int $currentLevel, bool $isGlobalAsset, bool $isPhpSpaRequest, bool $isJS, bool $return = false)
   {
      $indexes = $assetInfo['assetIndexes'];
      $isCss = $assetInfo['assetType'] === 'css';

      // --- Collect all callables ---
      $callables = [];
      $names = [];
      $maxMtime = 0;
      $firstFileName = null;

      if ($isGlobalAsset) {
         $isScopedGlobal = \str_starts_with($assetInfo['componentRoute'], '__global__:');
         $sourceArray = $isCss ? $this->stylesheets : $this->scripts;
         foreach ($indexes as $idx) {
            $item = $sourceArray[$idx] ?? null;

            // --- Scoped global: the scope id can be reused after an App is
            // --- removed, so a stale URL may land on a different App that holds
            // --- the same id. If ANY requested index is missing on this app, the
            // --- combined URL does not belong here -> skip (404) rather than
            // --- serve a partial / mismatched bundle. ---
            if ($isScopedGlobal && $item === null) {
               return;
            }

            if ($item && \is_callable($item['content'])) {
               $callables[$idx] = $item['content'];
               $names[$idx] = $item['name'] ?? ($isCss ? "stylesheet #$idx" : "script #$idx");
               $ff = new FileFunction($item['content']);
               $maxMtime = \max($maxMtime, $ff->getFileModificationTime());
               if ($firstFileName === null) $firstFileName = $ff->getFileName();
            }
         }
      } else {
         $component = $this->findComponentByRoute($assetInfo['componentRoute']);
         if ($component === null) return;

         $sourceArray = $isCss
            ? CallableInspector::getProperty($component, 'stylesheets')
            : CallableInspector::getProperty($component, 'scripts');

         foreach ($indexes as $idx) {
            $item = $sourceArray[$idx] ?? null;
            if ($item && \is_callable($item['content'])) {
               $callables[$idx] = $item['content'];
               $names[$idx] = $item['name'] ?? ($isCss ? "stylesheet #$idx" : "script #$idx");
               $ff = new FileFunction($item['content']);
               $maxMtime = \max($maxMtime, $ff->getFileModificationTime());
               if ($firstFileName === null) $firstFileName = $ff->getFileName();
            }
         }
      }

      if (empty($callables)) return;

      // --- Check generated cache ---
      $indexesKey = \implode(',', $indexes);
      $hashed = md5($assetInfo['componentRoute'] . $indexesKey . $maxMtime . $assetInfo['assetType']);

      if (isset(static::$staticFilesCache[$hashed])) {
         $content = static::$staticFilesCache[$hashed];
         goto FINALIZE_COMBINED;
      }

      $fileDir = $this->generatedCacheDirectory ?? ($firstFileName ? (pathinfo($firstFileName, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'generated') : null);
      $newName = $fileDir ? ($fileDir . DIRECTORY_SEPARATOR . $hashed . ".generated.php") : null;

      if ($newName && file_exists($newName)) {
         if ($currentLevel === Compressor::LEVEL_NONE) {
            unlink($newName);
         } else {
            $content = require $newName;
            $content = Compressor::applyBinaryCompression($content);
            // --- Save to cache ---
            static::$staticFilesCache[$hashed] = $content;
            goto FINALIZE_COMBINED;
         }
      }

      // --- Concatenate all content ---
      // Each part is prefixed with a labeled comment banner so the combined
      // bundle is navigable. For JS, parts are also separated ASI-safely: a part
      // ending `})()` followed by one starting `(` would otherwise fuse into
      // `})()(...)` → "is not a function". We trim trailing whitespace/semicolons
      // off each part and terminate it with a semicolon on its OWN line (`\n;\n`)
      // so even a dangling `// line comment` at the end of a part cannot swallow
      // the terminator. CSS has no such hazard (comment banner + newline only).
      $buffer = '';
      foreach ($callables as $idx => $callable) {
         $part = \call_user_func($callable);
         if (\is_array($part)) $part = $part[0] ?? '';

         $label = $names[$idx] ?? "#$idx";
         $banner = "\n/* ====================== {$label} ====================== */\n";

         if ($isCss) {
            $buffer .= $banner . $part . "\n";
         } else {
            // Trim trailing whitespace + semicolons, then terminate on its own
            // line so a trailing line-comment can't eat the semicolon.
            $part = \rtrim($part);
            $part = \rtrim($part, ';');
            $part = \rtrim($part);
            if ($part === '') continue;
            $buffer .= $banner . $part . "\n;\n";
         }
      }

      // --- Compress the concatenated content ---
      $compressionLevel = $isPhpSpaRequest ? Compressor::LEVEL_EXTREME : $currentLevel;

      if ($currentLevel !== Compressor::LEVEL_NONE) {
         $timer = new Timer();
         $assetType = strtoupper($assetInfo['assetType']);

         $timer->start();
         $content = $this->compressAssetContent($buffer, $compressionLevel, $assetInfo['type'], $isGlobalAsset ? 'global' : 'scoped');
         $count = \count($indexes);
         if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) error_log("Compressed combined $assetType asset ({$count} files) within {$timer->getFormattedElapsedTime()}");

         // --- Cache the result ---
         if (!$isPhpSpaRequest && $newName) {
            // Race-safe: concurrent combined-asset requests can all pass the
            // is_dir() check and then race into mkdir(), so the losers would
            // throw "File exists". Suppress and re-verify instead — a dir that
            // now exists (whoever created it) is success, not a fatal.
            if (!is_dir($fileDir) && !@mkdir($fileDir, 0755, true) && !is_dir($fileDir)) {
               if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
                  error_log("phpspa: failed to create generated cache directory: $fileDir");
               }
            }
            if (OxcCompressor::isErrorOutput($content) && !($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
               error_log("phpspa: caching Oxc parse-error output for combined $assetType asset (will regenerate when a source changes)");
            }
            if (!@file_put_contents($newName, "<?php\nreturn <<<'$assetType'\n$content\n$assetType;")) {
               if (!($this->disableLogs & DISABLE_COMPRESSION_LOG)) {
                  error_log("phpspa: failed to cache combined asset: $newName");
               }
            }
         }
      } else {
         $content = $buffer;
         // --- Wrap component JS in IIFE ---
         if (!$isGlobalAsset && $isJS) {
            $content = "(()=>{{$content}})()";
         }
      }

      // --- Apply binary compression (gzip/deflate) if requested ---
      $content = Compressor::applyBinaryCompression($content);
      static::$staticFilesCache[$hashed] = $content;

      FINALIZE_COMBINED:

      $this->setAssetHeaders($assetInfo['type']);
      if ($return) return $content;
      echo $content;
      exit(0);
   }

   /**
    * Find a component by its route
    *
    * @param string $targetRoute The route to search for
    * @return IComponent|Component|null The component if found, null otherwise
    */
   private function findComponentByRoute (string $targetRoute): IComponent|Component|null
   {
      foreach ($this->components as $component) {
         $route = CallableInspector::getProperty($component, 'route');

         // --- '__root__' is the sentinel for an empty / match-all (null) route. ---
         if ($targetRoute === '__root__' && ($route === null || $route === '')) {
            return $component;
         }

         if (\is_array($route)) {
            if (\in_array($targetRoute, $route)) {
               return $component;
            }
         }
         elseif ($route === $targetRoute) {
            return $component;
         }
      }

      return null;
   }

   /**
    * Stable identity for THIS App instance's global asset scope.
    *
    * Global scripts/stylesheets are registered per-App (`$this->scripts`,
    * `$this->stylesheets`), but a global asset link previously encoded only the
    * literal `__global__` route with an index. When two separate `new App()`
    * instances each registered global callables, both produced identical
    * `__global__` + index links, so whichever App's `run()` intercepted the
    * asset request would serve ITS OWN global at that index -- leaking one App's
    * global script into another App.
    *
    * The id disambiguates the owner. It is derived from the CONTENT identity of
    * this App's registered globals (each callable's defining file, start line
    * and modification time), not from construction order. A monotonic counter
    * was rejected because any positional id shifts when an App is removed: drop
    * App B and App C becomes the "second" App, inheriting B's old id and
    * matching B's stale cached URLs -- serving the wrong bytes with a 200. A
    * content hash is position-independent, so removing one App never changes
    * another App's id, and a stale URL for a removed App matches nobody.
    *
    * Two Apps that register byte-identical globals DO hash to the same id, but
    * then their global assets are literally the same, so serving one for the
    * other is harmless. Computed from reflection + filemtime only -- the global
    * callables are NEVER invoked to derive the id. Cached per instance; the
    * cache is invalidated implicitly because registration happens before the
    * first link generation.
    *
    * @return string The content-derived scope id for this app's globals
    */
   private function globalScopeId (): string
   {
      if ($this->scopeIdCache !== null) {
         return $this->scopeIdCache;
      }

      $signatures = [];

      foreach ([$this->scripts, $this->stylesheets] as $kind => $assets) {
         foreach ($assets as $asset) {
            $content = $asset['content'] ?? null;

            if (\is_callable($content)) {
               try {
                  $ff = new FileFunction($content);
                  $fn = $ff->getFunction();
                  $file = $ff->getFileName();
                  $line = $fn->getStartLine();
                  $mtime = $ff->getFileModificationTime();
                  $signatures[] = "{$kind}:{$file}:{$line}:{$mtime}";
               } catch (\Throwable) {
                  // --- Unreflectable callable: fall back to a spl hash so it
                  // --- still contributes a stable-per-process signature. ---
                  $signatures[] = "{$kind}:" . \spl_object_hash((object) ['c' => $content]);
               }
            } elseif (\is_string($content)) {
               // --- A string asset (external URL / literal) is its own identity. ---
               $signatures[] = "{$kind}:str:{$content}";
            }
         }
      }

      // --- Sort so registration order does not change the id; an App with no
      // --- globals hashes an empty set to a stable constant. ---
      \sort($signatures);
      $this->scopeIdCache = \substr(\md5(\implode('|', $signatures)), 0, 12);

      return $this->scopeIdCache;
   }

   /**
    * Per-instance cache for the content-derived scope id. Reset implicitly with
    * the object; recomputed on first use after all globals are registered.
    *
    * @return void
    */
   protected function initGlobalScope (): void
   {
      $this->scopeIdCache = null;
   }

   /**
    * Reset the per-request asset statics. Call at the start of a request in
    * long-lived worker runtimes (Swoole/RoadRunner) so a warm process does not
    * reuse a previous request's memoized asset decode. One-shot web SAPIs do
    * not need it (statics die with the process). The scope id is per-instance
    * and content-derived, so it needs no reset.
    *
    * @return void
    */
   public static function resetAssetRequestState (): void
   {
      self::$resolvedAssetInfo = null;
      self::$resolvedAssetUri = null;
   }

   /**
    * Decode + HMAC-verify + expiry-check the asset URL ONCE per request, then
    * memoize the result so every subsequent App's run() reuses it instead of
    * re-validating the same link.
    *
    * In a multi-App request the asset URL is identical for every App, so the
    * expensive base64 + HMAC verification only needs to happen for the first one.
    * The memo is keyed by the request URI, so a different request (worker reuse,
    * a multi-request test run) never reuses a stale decode.
    *
    * @param string $uri The current request URI.
    * @return array|null The decoded asset info, or null when not an asset.
    */
   private static function resolveAssetRequestMemoized (string $uri): ?array
   {
      if (self::$resolvedAssetUri === $uri) {
         return self::$resolvedAssetInfo === false ? null : self::$resolvedAssetInfo;
      }

      $info = AssetLinkManager::resolveAssetRequest($uri);
      self::$resolvedAssetUri = $uri;
      self::$resolvedAssetInfo = $info ?? false;
      return $info;
   }

   /**
    * Classify a global asset request against THIS App instance.
    *
    * @param string $componentRoute The decoded route from the asset link
    * @return int One of: 0 = not a global asset (component-scoped);
    *             1 = global owned by this app (serve it);
    *             -1 = global owned by a DIFFERENT app (do not serve; fall through).
    */
   private function globalAssetOwnership (string $componentRoute): int
   {
      // --- Bare `__global__` = framework-owned (e.g. the phpspa SPA script at
      // --- index -1). Every app serves it identically. ---
      if ($componentRoute === '__global__') {
         return 1;
      }

      // --- Scoped `__global__:<appId>` = a per-App global. Only the app whose
      // --- id matches may serve it; otherwise fall through so the owning app's
      // --- run() handles the request. ---
      if (\str_starts_with($componentRoute, '__global__:')) {
         $id = \substr($componentRoute, \strlen('__global__:'));
         return $id === $this->globalScopeId() ? 1 : -1;
      }

      return 0;
   }

   /**
    * Get asset content from component
    *
    * @param IComponent|Component $component The component containing the asset
    * @param array $assetInfo Asset information
    * @return string|null The asset content if found, null otherwise
    */
   private function getAssetContent (IComponent|Component $component, array $assetInfo): ?string
   {
      if ($assetInfo['assetType'] === 'css') {
         $stylesheets = CallableInspector::getProperty($component, 'stylesheets');
         $stylesheet = $stylesheets[$assetInfo['assetIndex']] ?? null;
         $stylesheetCallable = $stylesheet['content'] ?? null;

         if ($stylesheetCallable && is_callable($stylesheetCallable))
            return \call_user_func($stylesheetCallable);
      }
      elseif ($assetInfo['assetType'] === 'js') {
         $scripts = CallableInspector::getProperty($component, 'scripts');
         $script = $scripts[$assetInfo['assetIndex']] ?? null;
         $scriptCallable = $script['content'] ?? null;

         if ($scriptCallable && is_callable($scriptCallable))
            return \call_user_func($scriptCallable);
      }

      return null;
   }

   /**
    * Get global asset content from the application
    *
    * @param array $assetInfo Asset information
    * @return string|array|null The asset content if found, null otherwise
    */
   private function getGlobalAssetContent (array $assetInfo, $callable): array|string|null
   {
      $request = new HttpRequest();

      if ($assetInfo['assetType'] === 'css') {
         if ($callable && \is_callable($callable))
            return \call_user_func($callable);
      }
      elseif ($assetInfo['assetType'] === 'js') {
         if (\is_callable($callable)) {
            // --- Global scripts are intentionally global, no IIFE wrapping needed ---
            return \call_user_func($callable);
         }

         if ($assetInfo['assetIndex'] === -1 && $request->requestedWith() !== 'PHPSPA_REQUEST_SCRIPT' && $request->requestedWith() !== 'PHPSPA_REQUEST') {
            return [file_get_contents($this->getPhpSPAScriptPath())];
         }
      }

      return null;
   }

   public function getPhpSPAScriptPath (): string
   {
      return realpath(dirname(__DIR__, 4) . '/src/script/phpspa.min.js');
   }

   /**
    * Compress asset content
    *
    * @param string $content The content to compress
    * @param int $level Compression level
    * @param string $type Asset type ('css' or 'js')
    * @param string $scope Compression scope ('global' or 'scoped')
    * @return string Compressed content
    */
   private function compressAssetContent (string $content, int $level, string $type, string $scope): string
   {
      $type = match ($type) {
         'css' => 'CSS',
         'js' => 'JS',
         default => 'HTML',
      };

      return Compressor::compressWithLevel($content, $level, $type, $scope, $this->resolveJsEngine(), $this->disableLogs);
   }

   /**
    * Resolve which JS minification engine to use for this request, by priority.
    *
    * Priority (highest first):
    *   1. Oxc — if `useOxcForJsMinification()` was called. Oxc is preload-only;
    *      when it is enabled but not preloaded/available it degrades to PHP
    *      (never C++), or throws under `forceNativeCompression()`.
    *   2. If BOTH Oxc and esbuild are enabled, Oxc wins when it is actually
    *      available (preloaded); otherwise esbuild handles the request. This uses
    *      the real preload signal — there is no path-based tie-break, because Oxc
    *      cannot be loaded from a runtime path (it must be in `ffi.preload`).
    *   3. esbuild — if `useEsbuildForJsMinification()` was called.
    *   4. native C++ — the default, zero-process in-process minifier.
    *
    * @return string engine enum['oxc', 'esbuild', 'native']
    */
   private function resolveJsEngine(): string
   {
      if ($this->useOxc) {
         // When only Oxc is on, it always takes the request (and HtmlCompressor
         // decides available-Oxc vs PHP-fallback/throw). When both are on, defer
         // to esbuild only if Oxc isn't actually preloaded/available.
         if (!$this->useEsbuild || OxcCompressor::isAvailable()) {
            return 'oxc';
         }
      }

      if ($this->useEsbuild) {
         return 'esbuild';
      }

      return 'native';
   }

   /**
    * Set appropriate headers for asset response
    *
    * @param string $type Asset type ('css' or 'js')
    * @return void
    */
   private function setAssetHeaders (string $type): void
   {
      if (!\headers_sent()) {
         if ($type === 'css') header('Content-Type: text/css; charset=UTF-8');
         elseif ($type === 'js') header('Content-Type: application/javascript; charset=UTF-8');

         header('Cache-Control: public, max-age=' . AssetLinkManager::getCacheConfig()['hours'] * 3600 . ', immutable');
      }
   }

   /**
    * Inject global assets in optimal locations for proper loading order
    *
    * @param string $html The HTML content
    * @param array|string $globalAssets Array containing 'scripts' and 'stylesheets' keys
    * @param string $componentScripts Component scripts to inject after global scripts
    * @return string Modified HTML with global assets injected
    */
   private function injectGlobalAssets (string $html, array|string $globalAssets, string $componentScripts = ''): string
   {
      if (\is_string($globalAssets)) {
         $globalScripts = $globalAssets;
         $globalStylesheets = '';
      } else {
         $globalStylesheets = $globalAssets['stylesheets'];
         $globalScripts = $globalAssets['scripts'];
      }

      // --- Inject global stylesheets in head for proper CSS cascading ---
      if (!empty(trim($globalStylesheets))) {
         $html = preg_replace('/<\/head>/i', "{$globalStylesheets}</head>", $html, 1);
      }

      // --- If no global assets and no component scripts, return unchanged ---
      if (empty(trim($globalScripts)) && empty(trim($componentScripts))) {
         return $html;
      }

      // --- Combine global scripts and component scripts in proper order ---
      $allScripts = $globalScripts . $componentScripts;

      // --- Inject scripts at end of body (global scripts first, then component scripts) ---
      if (!empty(trim($allScripts))) {
         if (preg_match('/<\/body>/i', $html))
            $html = preg_replace('/<\/body>/i', "{$allScripts}</body>", $html, 1);
         elseif (preg_match('/<\/html>/i', $html))
            // --- If no body tag, try to inject before closing html tag ---
            $html = preg_replace('/<\/html>/i', "{$allScripts}</html>", $html, 1);
         else
            // --- If neither body nor html tags exist, append at the end ---
            $html .= $allScripts;
      }

      return $html;
   }

   /**
    * Executes the component function based on its parameter signature.
    *
    * @param Request $request The current HTTP request.
    * @param callable|string $componentFunction The component's callable function.
    * @param array $routerParams An associative array of route parameters.
    * @return string The output of the component function.
    */
   private function executeComponentFunction(Request $request, callable|string $componentFunction, array $routerParams = []): string
   {
      if (CallableInspector::hasParam($componentFunction, 'path') && CallableInspector::hasParam($componentFunction, 'request')) {
         return (string) \call_user_func(
            $componentFunction,
            path: $routerParams,
            request: $request,
         );
      } elseif (CallableInspector::hasParam($componentFunction, 'path')) {
         return (string) \call_user_func(
            $componentFunction,
            path: $routerParams,
         );
      } elseif (CallableInspector::hasParam($componentFunction, 'request')) {
         return (string) \call_user_func(
            $componentFunction,
            request: $request,
         );
      } else {
         return (string) \call_user_func($componentFunction, $request, $routerParams);
      }
   }
}
