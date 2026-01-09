<?php

namespace PhpSPA\Core\Impl\RealImpl;

use PhpSPA\DOM;
use PhpSPA\Component;
use PhpSPA\Http\Request;
use PhpSPA\Http\Response;
use PhpSPA\Http\Session;
use PhpSPA\Http\Security\Nonce;
use PhpSPA\Core\Router\MapRoute;
use PhpSPA\Core\Router\PrefixRouter;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Core\Helper\CsrfManager;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Helper\CallableInspector;
use PhpSPA\Core\Helper\ComponentScope;
use PhpSPA\Core\Helper\AssetLinkManager;
use PhpSPA\Core\Helper\PathResolver;
use PhpSPA\Core\Utils\Formatter\ComponentTagFormatter;
use PhpSPA\Core\Utils\Validate;
use PhpSPA\Interfaces\ApplicationContract;
use PhpSPA\Interfaces\IComponent;

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
    * @var Component|IComponent[]
    */
   private array $components = [];

   /**
    * @var callable[]
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
    *    handler: callable
    * }>
    */
   protected array $prefix = [];

   private bool $module = false;

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

   public function middleware (callable $component): ApplicationContract
   {
      $this->middlewares[] = $component;
      return $this;
   }

   public function cors (array $data = []): ApplicationContract
   {
      $this->cors = require __DIR__ . '/../../Config/Cors.php';

      if (!empty($data)) {
         $this->cors = array_merge_recursive($this->cors, $data);
      }

      foreach ($this->cors as $key => $value) {
         if (\is_array($value)) {
            $this->cors[$key] = array_unique($value);
         }
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

   public function prefix(string $path, callable $handler): ApplicationContract {
      $this->prefix[] = ['path' => $path, 'handler' => $handler]; return $this;
   }

   public function useModule(): ApplicationContract {
      $this->module = true;
      return $this;
   }

   public function run (bool $return = false)
   {
      $request = new HttpRequest();

      $this->resolveStaticPath();
      $this->resolveCors($request);
      $this->handlePhpSPARequest($request);

      /**
       * Handle asset requests (CSS/JS files from session-based links)
       */
      // Auto-detect and set base path for proper asset URL resolution
      PathResolver::autoDetectBasePath();

      $assetInfo = AssetLinkManager::resolveAssetRequest(static::$request_uri);
      if ($assetInfo !== null) {
         $this->serveAsset($assetInfo);
         exit();
      }

      // Clean up expired asset mappings periodically
      AssetLinkManager::cleanupExpiredMappings();

      $success = false;

      foreach ($this->components as $component) {
         $output = $this->runComponent($component, false, $this->renderedData);

         if ($output === true) {
            $success = true;
            break;
         }
      }

      if ($success === true) {
         $compressedOutput = Compressor::compress($this->renderedData, 'text/html');

         if ($return) return $compressedOutput;

         print_r($compressedOutput);
         exit(0);
      }

      foreach ($this->prefix as $prefix) {
         $this->handlePrefix($prefix);
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

   private function handlePhpSPARequest(Request $request) {
      if ($request->requestedWith() === 'PHPSPA_REQUEST' && $request->isSameOrigin()) {
         if ($request->header('X-Phpspa-Target') === 'navigate') {
            Session::remove(STATE_HANDLE);
            Session::remove(CALL_FUNC_HANDLE);
            return;
         }

         $data = json_decode(base64_decode($request->auth()->bearer ?? ''), true);
         $data = Validate::validate($data);

         if (isset($data['state'])) {
            $state = $data['state'];

            if (!empty($state['key'])) {
               $sessionData = SessionHandler::get(STATE_HANDLE);
               $sessionData[$state['key']] = @$state['value'];
               SessionHandler::set(STATE_HANDLE, $sessionData);
            }

            return;
         }

         if (isset($data['__call'])) {
            try {
               $tokenData = base64_decode($data['__call']['token'] ?? '');
               $tokenData = json_decode($tokenData);

               $token = $tokenData[1] ?? null;
               $functionName = $tokenData[0] ?? null;
               $use_once = $tokenData[3] ?? false;
               $csrf = new CsrfManager($functionName, CALL_FUNC_HANDLE);

               if ($csrf->verifyToken($token, $use_once)) {
                  $res = \call_user_func_array(
                     $functionName,
                     $data['__call']['args'],
                  );
                  print_r(
                     json_encode([
                        'response' => json_encode($res),
                     ]),
                  );
               }
               else {
                  throw new \Exception('Invalid or Expired Token');
               }
            }
            catch ( \Exception $e ) {
               print_r($e->getMessage());
            }
            exit();
         }
      }
   }


   private function runComponent(Component|Icomponent $component, bool $isPreloadingComponent = false, ?string &$layoutOutput = null) {
      static $request = null;
      static $router = null;

      if ($request === null) {
         $request = new HttpRequest();
      }

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
         $routerMap = new MapRoute($method, $route, $caseSensitive, $pattern);
         $router = $routerMap->match();

         if (!$router)
            return; // --- Skip if no match found ---

         $request = new HttpRequest($router['params'] ?? []);

         DOM::CurrentRoutes(static::$request_uri);

         Session::remove(STATE_HANDLE);
         Session::remove(CALL_FUNC_HANDLE);
      }
      
      if ($isPreloadingComponent && !str_contains($route[0] ?? '', '{')) {
         DOM::CurrentRoutes($route[0] ?? '');
      }

      if ($name) DOM::CurrentComponents($name);

      // --- Merge component meta data only if this is the correct route ---
      $metaTags = [...$this->metadata, ...$componentMetaData];
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
            'content' => Compressor::compressComponent($componentOutput),
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
      $nonce = Nonce::nonce();

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

      $isFirstComponent = $isFirstComponent === null ? true : false;

      if (!$isPreloadingComponent) {
         if ($title) {
            $count = 0;
            $layoutOutput = preg_replace_callback(
               pattern: '/<title\b([^>]*)>.*?<\/title>/si',
               callback: fn ($matches) =>
                  // --- $matches[1] contains any attributes inside the <title> tag ---
                  "\n      <title" . ($matches[1] ?? null) . '>' . $title . '</title>',
               subject: $layoutOutput,
               limit: -1,
               count: $count,
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

         if ($nonce) {
            $layoutOutput = preg_replace(
               '/<head\b([^>]*)>/i',
               "<head$1 x-phpspa=\"$nonce\">",
               $layoutOutput,
               1,
            );
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
            $targetInformation['defaultContent'][] = Compressor::compressComponent($matches[3]);

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

      // --- Generate global stylesheet links ---
      if (!empty($globalStylesheets)) {
         foreach ($globalStylesheets as $index => $stylesheet) {
            $stylesheet = (array) Validate::validate($stylesheet);

            if (is_callable($stylesheet['content'])) {
               $stylesheet['type'] ??= 'text/css';
               $stylesheet['href'] = AssetLinkManager::generateCssLink("__global__", $index, $stylesheet['name'] ?? null);
            } else
               $stylesheet['href'] = $stylesheet['content'];

            unset($stylesheet['name']);
            unset($stylesheet['content']);
            $attributes = HTMLAttrInArrayToString($stylesheet);
            $result['global']['stylesheets'] .= "\n      <link $attributes />";
         }
      }

      // --- Generate component stylesheet links ---
      if (!empty($stylesheets)) {
         foreach ($stylesheets as $index => $stylesheet) {
            $stylesheet = (array) Validate::validate($stylesheet);

            if (is_callable($stylesheet['content'])) {
               $stylesheet['type'] ??= 'text/css';
               $stylesheet['href'] = AssetLinkManager::generateCssLink($primaryRoute, $index, $stylesheet['name'] ?? null);
            } else
               $stylesheet['href'] = $stylesheet['content'];

            unset($stylesheet['name']);
            unset($stylesheet['content']);
            $attributes = HTMLAttrInArrayToString($stylesheet);
            $result['component']['stylesheets'] .= "\n      <link $attributes />";
         }
      }

      // --- Automatically add phpspa script for SPA functionality ---
      // --- Attaching it to the global stylesheet to expicitly... ---
      // --- add it to the head tag alongside with the styles instead of the body ---
      if (!$isPhpSpaRequest && !$this->module) {
         $jsLink = AssetLinkManager::generateJsLink("__global__", -1, null, 'text/javascript');
         $result['global']['stylesheets'] .= "\n      <script type=\"text/javascript\" src=\"$jsLink\"></script>\n";
      }

      // --- Generate global script links ---
      if (!empty($globalScripts)) {
         foreach ($globalScripts as $index => $script) {
            $script = (array) Validate::validate($script);
            $isLink = false;

            if (is_callable($script['content']))
               $script['src'] = AssetLinkManager::generateJsLink("__global__", $index, $script['name'] ?? null, $script['type']);
            else {
               $script['src'] = $script['content'];
               $isLink = true;
            }

            unset($script['name']);
            unset($script['content']);
            $attributes = HTMLAttrInArrayToString($script);

            $result['global']['scripts'] .= $isPhpSpaRequest && !$isLink
               ? "\n      <phpspa-script $attributes></phpspa-script>"
               : "\n      <script $attributes></script>";
         }
      }

      // --- Generate component script links ---
      if (!empty($scripts)) {
         foreach ($scripts as $index => $script) {
            $script = (array) Validate::validate($script);
            $isLink = false;

            if (is_callable($script['content']))
               $script['src'] = AssetLinkManager::generateJsLink($primaryRoute, $index, $script['name'] ?? null, $script['type']);
            else {
               $script['src'] = $script['content'];
               $isLink = true;
            }

            unset($script['name']);
            unset($script['content']);
            $attributes = HTMLAttrInArrayToString($script);

            $result['component']['scripts'] .= $isPhpSpaRequest && !$isLink
               ? "\n      <phpspa-script $attributes></phpspa-script>"
               : "\n      <script $attributes></script>";
         }
      }

      return $result;
   }

   /**
    * Serve CSS/JS asset content from session-based links
    *
    * @param array $assetInfo Asset information from AssetLinkManager
    * @return void
    */
   private function serveAsset (array $assetInfo): void
   {
      $request = new HttpRequest();

      // --- Check if this is a global asset ---
      if ($assetInfo['componentRoute'] === '__global__') {
         $content = $this->getGlobalAssetContent($assetInfo);
      }
      else {
         // --- Find the component that matches the asset's route ---
         $component = $this->findComponentByRoute($assetInfo['componentRoute']);

         if ($component === null) {
            http_response_code(Response::StatusNotFound);
            header('Content-Type: text/plain');
            echo "Asset not found";
            return;
         }

         $isRealJavascript = strtolower($assetInfo['scriptType']) === 'application/javascript' || strtolower($assetInfo['scriptType']) === 'text/javascript';

         if ($assetInfo['assetType'] === 'js' && $isRealJavascript)
            // --- For JS, we wrap the content in an IIFE to avoid polluting global scope ---
            $content = '(()=>{' . $this->getAssetContent($component, $assetInfo) . '})();';
         else
            // --- For CSS, we can serve the content directly ---
            $content = $this->getAssetContent($component, $assetInfo);
      }

      if ($content === null) {
         http_response_code(Response::StatusNotFound);
         header('Content-Type: text/plain');
         echo "Asset content not found";
         return;
      }

      $currentLevel = Compressor::getLevel();

      // --- Determine compression level ---
      $compressionLevel = ($request->requestedWith() === 'PHPSPA_REQUEST')
         ? ($currentLevel === Compressor::LEVEL_NONE ? $currentLevel : Compressor::LEVEL_EXTREME)
         : $currentLevel;

      if (\is_array($content)) {
         $content = $content[0];
      } else {
         // --- Compress the content ---
         $content = $this->compressAssetContent($content, $compressionLevel, $assetInfo['type']);
      }

      // --- Set appropriate headers ---
      $this->setAssetHeaders($assetInfo['type'], $content);
      // --- Output the content ---
      echo $content;
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
   private function getGlobalAssetContent (array $assetInfo): string|array|null
   {
      $request = new HttpRequest();

      if ($assetInfo['assetType'] === 'css') {
         $stylesheet = $this->stylesheets[$assetInfo['assetIndex']] ?? null;
         $stylesheetCallable = $stylesheet['content'] ?? null;

         if ($stylesheetCallable && \is_callable($stylesheetCallable))
            return \call_user_func($stylesheetCallable);
      }
      elseif ($assetInfo['assetType'] === 'js') {
         $script = $this->scripts[$assetInfo['assetIndex']] ?? null;
         $scriptCallable = $script['content'] ?? null;

         $isRealJavascript = strtolower($assetInfo['scriptType']) === 'application/javascript' || strtolower($assetInfo['scriptType']) === 'text/javascript';

         if (\is_callable($scriptCallable)) {
            $content = \call_user_func($scriptCallable);

            if ($request->requestedWith() === 'PHPSPA_REQUEST_SCRIPT' && $isRealJavascript)
               // --- Wrap global JS content in an IIFE to avoid polluting global scope ---
               return '(()=>{' . $content . '})();';

            // --- For non-PHPSPA requests, return raw JS content ---
            return $content;
         }

         if ($assetInfo['assetIndex'] === -1 && $request->requestedWith() !== 'PHPSPA_REQUEST_SCRIPT' && $request->requestedWith() !== 'PHPSPA_REQUEST') {
            $scriptPath = dirname(__DIR__, 4);
            $path = '/src/script/phpspa.min.js'; // --- PRODUCTION ---

            return [file_get_contents($scriptPath . $path)];
         }
      }

      return null;
   }

   /**
    * Compress asset content
    *
    * @param string $content The content to compress
    * @param int $level Compression level
    * @param string $type Asset type ('css' or 'js')
    * @return string Compressed content
    */
   private function compressAssetContent (string $content, int $level, string $type): string
   {
      if ($type === 'css')
         return Compressor::compressWithLevel($content, $level, 'CSS');
      elseif ($type === 'js')
         return Compressor::compressWithLevel($content, $level, 'JS');

      return Compressor::compressWithLevel($content, $level, 'HTML');
   }

   /**
    * Set appropriate headers for asset response
    *
    * @param string $type Asset type ('css' or 'js')
    * @param string $content The content to send
    * @return void
    */
   private function setAssetHeaders (string $type, string $content): void
   {
      if (!headers_sent()) {
         if ($type === 'css') header('Content-Type: text/css; charset=UTF-8');
         elseif ($type === 'js') header('Content-Type: application/javascript; charset=UTF-8');

         header('Content-Length: ' . \strlen($content));
         header('Cache-Control: private, max-age=' . (AssetLinkManager::getCacheConfig()['hours'] * 3600));
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
    * @param callable $componentFunction The component's callable function.
    * @param array $routerParams An associative array of route parameters.
    * @return string The output of the component function.
    */
   private function executeComponentFunction(Request $request, callable $componentFunction, array $routerParams = []): string
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
