<?php

namespace PhpSPA\Core\Impl\RealImpl;

use PhpSPA\Component;
use PhpSPA\Http\Request;
use PhpSPA\Http\Session;
use PhpSPA\Http\Security\Nonce;
use PhpSPA\Core\Router\MapRoute;
use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Config\CompressionConfig;
use PhpSPA\Core\Helper\CsrfManager;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Helper\CallableInspector;
use PhpSPA\Core\Helper\ComponentScope;
use PhpSPA\Core\Helper\AssetLinkManager;
use PhpSPA\Core\Helper\PathResolver;
use PhpSPA\Core\Utils\Formatter\ComponentTagFormatter;
use PhpSPA\Interfaces\ApplicationContract;

use const PhpSPA\Core\Impl\Const\STATE_HANDLE;
use const PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

/**
 * Core application implementation class
 * This abstract class provides the foundational implementation for the PhpSPA application framework.
 * It handles layout management, component registration,
 * routing, and rendering logic that powers the single-page application experience.
 *
 * @package PhpSPA\Core\Impl\RealImpl
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @var string $request_uri
 * @abstract
 */
abstract class AppImpl implements ApplicationContract {
    use ComponentTagFormatter;
    use \PhpSPA\Core\Utils\Validate;

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
     * @var Component[]
     */
    private array $components = [];

    /**
     * Indicates whether the application should treat string comparisons as case sensitive.
     *
     * @var bool Defaults to false, meaning string comparisons are case insensitive by default.
     */
    private bool $defaultCaseSensitive = false;

    /**
     * The base URI of the application.
     * This is used to determine the root path for routing and resource loading.
     *
     * @var string
     */
    public static string $request_uri;

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
     * @var callable[]
     */
    protected array $scripts = [];

    /**
     * Global stylesheets to be included for the application.
     * These styles will be included on every component render.
     *
     * @var callable[]
     */
    protected array $stylesheets = [];

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

    public function attach (Component $component): ApplicationContract
    {
        $this->components[] = $component;
        return $this;
    }

    public function detach (Component $component): ApplicationContract
    {
        $key = array_search($component, $this->components, true);

        if ($key !== false) {
            unset($this->components[$key]);
        }
        return $this;
    }

    public function cors (array $data = []): ApplicationContract
    {
        $this->cors = require __DIR__ . '/../../Config/Cors.php';

        if (!empty($data)) {
            $this->cors = array_merge_recursive($this->cors, $data);
        }

        foreach ($this->cors as $key => $value) {
            if (is_array($value)) {
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

    public function script (callable $script, ?string $name = null): ApplicationContract
    {
        $this->scripts[] = [ $script, $name ];
        return $this;
    }

    public function styleSheet (callable $style, ?string $name = null): ApplicationContract
    {
        $this->stylesheets[] = [ $style, $name ];
        return $this;
    }

    public function run (): void
    {
        $request = new Request();

        if (!headers_sent()) {
            foreach ($this->cors as $key => $value) {
                $key =
                    'Access-Control-' . str_replace('_', '-', ucwords($key, '_'));
                $value = is_array($value) ? implode(', ', $value) : $value;

                $header_value =
                    $key .
                    ': ' .
                    (is_bool($value) ? var_export($value, true) : $value);
                header($header_value);
            }
        }

        /**
         * Handle preflight requests (OPTIONS method)
         */
        if ($request->isMethod('options')) {
            exit();
        }

        /**
         * Handle asset requests (CSS/JS files from session-based links)
         */
        // Auto-detect and set base path for proper asset URL resolution
        PathResolver::autoDetectBasePath();

        $assetInfo = AssetLinkManager::resolveAssetRequest(self::$request_uri);
        if ($assetInfo !== null) {
            $this->serveAsset($assetInfo);
            exit();
        }

        // Clean up expired asset mappings periodically
        AssetLinkManager::cleanupExpiredMappings();

        foreach ($this->components as $component) {
            $route = CallableInspector::getProperty($component, 'route');
            $method = CallableInspector::getProperty($component, 'method');
            $caseSensitive =
                CallableInspector::getProperty($component, 'caseSensitive') ??
                $this->defaultCaseSensitive;
            $targetID =
                CallableInspector::getProperty($component, 'targetID') ??
                $this->defaultTargetID;
            $componentFunction = CallableInspector::getProperty(
                $component,
                'component',
            );
            $scripts = CallableInspector::getProperty($component, 'scripts');
            $stylesheets = CallableInspector::getProperty(
                $component,
                'stylesheets',
            );
            $title = CallableInspector::getProperty($component, 'title');
            $reloadTime = CallableInspector::getProperty($component, 'reloadTime');

            if (!$componentFunction || !is_callable($componentFunction)) {
                continue;
            }

            if (!$route) {
                $m = explode('|', $method);
                $m = array_map('trim', $m);
                $m = array_map('strtolower', $m);

                if (
                !in_array(strtolower($_SERVER['REQUEST_METHOD']), $m) &&
                !in_array('*', $m)
                ) {
                    continue;
                }
            }
            else {
                $router = (new MapRoute())->match($method, $route, $caseSensitive);
                if (!$router) {
                    continue;
                } // Skip if no match found
            }

            // Clear component scope before each component execution
            ComponentScope::clearAll();

            $layoutOutput = is_callable($this->layout) ? (string) call_user_func($this->layout) : $this->layout;

            $componentOutput = '';

            if ($request->requestedWith() === 'PHPSPA_REQUEST') {
                if ($request->header('X-Phpspa-Target') === 'navigate') {
                    Session::remove(STATE_HANDLE);
                    Session::remove(CALL_FUNC_HANDLE);
                }

                $data = json_decode($request->auth()->bearer ?? '', true);
                $data = $this->validate($data);

                if (isset($data['state'])) {
                    $state = $data['state'];

                    if (!empty($state['key']) && !empty($state['value'])) {
                        $sessionData = SessionHandler::get(STATE_HANDLE);
                        $sessionData[$state['key']] = $state['value'];
                        SessionHandler::set(STATE_HANDLE, $sessionData);
                    }
                }

                if (isset($data['__call'])) {
                    try {
                        $tokenData = base64_decode($data['__call']['token'] ?? '');
                        $tokenData = json_decode($tokenData);

                        $token = $tokenData[1];
                        $functionName = $tokenData[0];
                        $csrf = new CsrfManager($functionName, CALL_FUNC_HANDLE);

                        if ($csrf->verifyToken($token, false)) {
                            $res = call_user_func_array(
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
            else if ($request->requestedWith() !== 'PHPSPA_REQUEST_SCRIPT') {
                Session::remove(STATE_HANDLE);
                Session::remove(CALL_FUNC_HANDLE);
            }

            /**
             * Invokes the specified component callback with appropriate parameters based on its signature.
             *
             * This logic checks if the component's callable accepts 'path' and/or 'request' parameters
             * using CallableInspector. It then calls the component with the corresponding arguments:
             * - If both 'path' and 'request' are accepted, both are passed.
             * - If only 'path' is accepted, only 'path' is passed.
             * - If only 'request' is accepted, only 'request' is passed.
             * - If neither is accepted, the component is called without arguments.
             *
             * @param object $component The component object containing the callable to invoke.
             * @param array $router An associative array containing 'params' and 'request' to be passed as arguments.
             */

            if (
            CallableInspector::hasParam($componentFunction, 'path') &&
            CallableInspector::hasParam($componentFunction, 'request')
            ) {
                $componentOutput = call_user_func(
                    $componentFunction,
                    path: $router['params'],
                    request: $request,
                );
            }
            elseif (CallableInspector::hasParam($componentFunction, 'path')) {
                $componentOutput = call_user_func(
                    $componentFunction,
                    path: $router['params'],
                );
            }
            elseif (CallableInspector::hasParam($componentFunction, 'request')) {
                $componentOutput = call_user_func(
                    $componentFunction,
                    request: $request,
                );
            }
            else {
                $componentOutput = call_user_func($componentFunction);
            }

            // Create a new scope for this component and execute formatting
            $scopeId = ComponentScope::createScope();
            $componentOutput = static::format($componentOutput);
            ComponentScope::removeScope($scopeId);

            // Generate session-based links for scripts and stylesheets instead of inline content
            $assetLinks = $this->generateAssetLinks($route, $scripts, $stylesheets, $this->scripts, $this->stylesheets);

            if ($request->requestedWith() === 'PHPSPA_REQUEST') {
                // For PHPSPA requests (component updates), include component scripts with the component
                $componentOutput = $assetLinks['component']['stylesheets'] . $componentOutput . $assetLinks['global']['scripts'] . $assetLinks['component']['scripts'];

                $info = [
                    'content' => Compressor::compressComponent($componentOutput),
                    'title' => $title,
                    'targetID' => $targetID,
                ];
                if ($reloadTime > 0) {
                    $info['reloadTime'] = $reloadTime;
                }

                // Use compressed JSON output
                print_r(Compressor::compressJson($info));
                exit(0);
            }
            else {
                // For regular HTML requests, only include component stylesheets with the component content
                // Component scripts will be injected later to ensure proper execution order
                $componentOutput = $assetLinks['component']['stylesheets'] . $componentOutput;
                $nonce = Nonce::nonce();

                if ($title) {
                    $count = 0;
                    $layoutOutput = preg_replace_callback(
                        '/<title([^>]*)>.*?<\/title>/si',
                        function ($matches) use ($title)
                        {
                            // $matches[1] contains any attributes inside the <title> tag
                            return '<title' . $matches[1] . '>' . $title . '</title>';
                        },
                        $layoutOutput,
                        -1,
                        $count,
                    );

                    if ($count === 0) {
                        // If no <title> tag was found, add one inside the <head> section
                        $layoutOutput = preg_replace(
                            '/<head([^>]*)>/i',
                            "<head$1><title>$title</title>",
                            $layoutOutput,
                            1,
                        );
                    }

                    if ($nonce) {
                        $layoutOutput = preg_replace(
                            '/<html([^>]*)>/i',
                            "<html$1 x-phpspa=\"$nonce\">",
                            $layoutOutput,
                            1,
                        );
                    }
                }
                elseif ($nonce) {
                    $layoutOutput = preg_replace(
                        '/<head([^>]*)>/i',
                        "<head$1 x-phpspa=\"$nonce\">",
                        $layoutOutput,
                        1,
                    );
                }

                $tt = '';
                $layoutOutput = static::format($layoutOutput) ?? '';

                if ($reloadTime > 0) {
                    $tt = " phpspa-reload-time=\"$reloadTime\"";
                }

                $tag =
                    '/<(\w+)([^>]*id\s*=\s*["\']?' .
                    preg_quote($targetID, '/') .
                    '["\']?[^>]*)>.*?<\/\1>/si';

                $this->renderedData = preg_replace_callback(
                    $tag,
                    function ($matches) use ($componentOutput, $tt)
                    {
                        // $matches[1] contains the tag name, $matches[2] contains attributes with the target ID
                        return '<' .
                            $matches[1] .
                            $matches[2] .
                            " data-phpspa-target$tt>" .
                            $componentOutput .
                            '</' .
                            $matches[1] .
                            '>';
                    },
                    $layoutOutput,
                );

                // Inject global assets at the end of body tag (or html tag if no body exists)
                // Also inject component scripts after global scripts for proper execution order
                $this->renderedData = $this->injectGlobalAssets($this->renderedData, $assetLinks['global'], $assetLinks['component']['scripts']);

                // Compress final HTML output before sending
                $compressedOutput = Compressor::compress(
                    $this->renderedData,
                    'text/html',
                );

                print_r($compressedOutput);
                exit(0);
            }
        }
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
        $request = new Request();
        $isPhpSpaRequest = $request->requestedWith() === 'PHPSPA_REQUEST' || $request->requestedWith() === 'PHPSPA_REQUEST_SCRIPT';

        $result = [
            'component' => [ 'scripts' => '', 'stylesheets' => '' ],
            'global' => [ 'scripts' => '', 'stylesheets' => '' ]
        ];

        // Automatically add phpspa script for SPA functionality
        if (!$isPhpSpaRequest) {
            $jsLink = AssetLinkManager::generateJsLink("__global__", -1, null);
            $result['global']['scripts'] .= "\n<script type=\"text/javascript\" src=\"$jsLink\"></script>\n";
        }
        // Get the primary route for mapping purposes
        $primaryRoute = is_array($route) ? $route[0] : $route;

        // Generate global stylesheet links
        if (!empty($globalStylesheets)) {
            foreach ($globalStylesheets as $index => $stylesheet) {
                $name = is_array($stylesheet) ? $stylesheet[1] : null;
                $stylesheetCallable = is_array($stylesheet) ? $stylesheet[0] : $stylesheet;
                if (is_callable($stylesheetCallable)) {
                    $cssLink = AssetLinkManager::generateCssLink("__global__", $index, $name);
                    $result['global']['stylesheets'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssLink\" />\n";
                }
            }
        }

        // Generate component stylesheet links
        if (!empty($stylesheets)) {
            foreach ($stylesheets as $index => $stylesheet) {
                $name = is_array($stylesheet) ? $stylesheet[1] : null;
                $stylesheetCallable = is_array($stylesheet) ? $stylesheet[0] : $stylesheet;
                if (is_callable($stylesheetCallable)) {
                    $cssLink = AssetLinkManager::generateCssLink($primaryRoute, $index, $name);
                    $result['component']['stylesheets'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssLink\" />\n";
                }
            }
        }

        // Generate global script links
        if (!empty($globalScripts)) {
            foreach ($globalScripts as $index => $script) {
                $name = is_array($script) ? $script[1] : null;
                $scriptCallable = is_array($script) ? $script[0] : $script;
                if (is_callable($scriptCallable)) {
                    $jsLink = AssetLinkManager::generateJsLink("__global__", $index, $name);
                    $result['global']['scripts'] .= $isPhpSpaRequest ? "\n<phpspa-script src=\"$jsLink\"></phpspa-script>\n" : "\n<script type=\"text/javascript\" data-type=\"phpspa/script\" src=\"$jsLink\"></script>\n";
                }
            }
        }

        // Generate component script links
        if (!empty($scripts)) {
            foreach ($scripts as $index => $script) {
                $name = is_array($script) ? $script[1] : null;
                $scriptCallable = is_array($script) ? $script[0] : $script;
                if (is_callable($scriptCallable)) {
                    $jsLink = AssetLinkManager::generateJsLink($primaryRoute, $index, $name);
                    $result['component']['scripts'] .= $isPhpSpaRequest ? "\n<phpspa-script src=\"$jsLink\"></phpspa-script>\n" : "\n<script type=\"text/javascript\" data-type=\"phpspa/script\" src=\"$jsLink\"></script>\n";
                }
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
        // Check if this is a global asset
        if ($assetInfo['componentRoute'] === '__global__') {
            $content = $this->getGlobalAssetContent($assetInfo);
        }
        else {
            // Find the component that matches the asset's route
            $component = $this->findComponentByRoute($assetInfo['componentRoute']);

            if ($component === null) {
                http_response_code(404);
                header('Content-Type: text/plain');
                echo "Asset not found";
                return;
            }

            if ($assetInfo['assetType'] === 'js') {
                // For JS, we wrap the content in an IIFE to avoid polluting global scope
                $content = '(()=>{' . $this->getAssetContent($component, $assetInfo) . '})();';
            }
            else {
                // For CSS, we can serve the content directly
                $content = $this->getAssetContent($component, $assetInfo);
            }
        }

        if ($content === null) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo "Asset content not found";
            return;
        }

        // Determine compression level
        $request = new Request();
        $compressionLevel = ($request->requestedWith() === 'PHPSPA_REQUEST')
            ? Compressor::LEVEL_EXTREME
            : Compressor::getLevel();

        // Compress the content
        $compressedContent = $this->compressAssetContent($content, $assetInfo['type'], $compressionLevel);

        // Set appropriate headers
        $this->setAssetHeaders($assetInfo['type'], $compressedContent);

        // Output the content
        echo $compressedContent;
    }

    /**
     * Find a component by its route
     *
     * @param string $targetRoute The route to search for
     * @return Component|null The component if found, null otherwise
     */
    private function findComponentByRoute (string $targetRoute): ?Component
    {
        foreach ($this->components as $component) {
            $route = CallableInspector::getProperty($component, 'route');

            if (is_array($route)) {
                if (in_array($targetRoute, $route)) {
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
     * @param Component $component The component containing the asset
     * @param array $assetInfo Asset information
     * @return string|null The asset content if found, null otherwise
     */
    private function getAssetContent (Component $component, array $assetInfo): ?string
    {
        if ($assetInfo['assetType'] === 'css') {
            $stylesheets = CallableInspector::getProperty($component, 'stylesheets');
            $stylesheet = $stylesheets[$assetInfo['assetIndex']] ?? null;
            $stylesheetCallable = is_array($stylesheet) ? $stylesheet[0] : $stylesheet;
            if ($stylesheetCallable && is_callable($stylesheetCallable)) {
                return call_user_func($stylesheetCallable);
            }
        }
        elseif ($assetInfo['assetType'] === 'js') {
            $scripts = CallableInspector::getProperty($component, 'scripts');
            $script = $scripts[$assetInfo['assetIndex']] ?? null;
            $scriptCallable = is_array($script) ? $script[0] : $script;
            if ($scriptCallable && is_callable($scriptCallable)) {
                return call_user_func($scriptCallable);
            }
        }

        return null;
    }

    /**
     * Get global asset content from the application
     *
     * @param array $assetInfo Asset information
     * @return string|null The asset content if found, null otherwise
     */
    private function getGlobalAssetContent (array $assetInfo): ?string
    {
        if ($assetInfo['assetType'] === 'css') {
            $stylesheet = $this->stylesheets[$assetInfo['assetIndex']] ?? null;
            $stylesheetCallable = is_array($stylesheet) ? $stylesheet[0] : $stylesheet;
            if ($stylesheetCallable && is_callable($stylesheetCallable)) {
                return call_user_func($stylesheetCallable);
            }
        }
        elseif ($assetInfo['assetType'] === 'js') {
            $script = $this->scripts[$assetInfo['assetIndex']] ?? null;
            $scriptCallable = is_array($script) ? $script[0] : $script;
            $request = new Request();

            if ($scriptCallable && is_callable($scriptCallable)) {
                $content = call_user_func($scriptCallable);

                if ($request->requestedWith() === 'PHPSPA_REQUEST_SCRIPT') {
                    // Wrap global JS content in an IIFE to avoid polluting global scope
                    return '(()=>{' . $content . '})();';
                }

                // For non-PHPSPA requests, return raw JS content
                return $content;
            }

            if ($assetInfo['assetIndex'] === -1 && $request->requestedWith() !== 'PHPSPA_REQUEST_SCRIPT' && $request->requestedWith() !== 'PHPSPA_REQUEST') {
                return file_get_contents(__DIR__ . '/../../../../src/script/phpspa.js');
            }
        }

        return null;
    }

    /**
     * Compress asset content
     *
     * @param string $content The content to compress
     * @param string $type Asset type ('css' or 'js')
     * @param int $level Compression level
     * @return string Compressed content
     */
    private function compressAssetContent (string $content, string $type, int $level): string
    {
        if ($type === 'css') {
            // Wrap CSS content in style tags for compression, then extract
            $wrappedContent = "<style>$content</style>";
            $compressed = Compressor::compressWithLevel($wrappedContent, $level);
            // Extract CSS content back from style tags
            if (preg_match('/<style[^>]*>(.*?)<\/style>/s', $compressed, $matches)) {
                return trim($matches[1]);
            }
            return $content;
        }
        elseif ($type === 'js') {
            // Wrap JS content in script tags for compression, then extract
            $wrappedContent = "<script>$content</script>";
            $compressed = Compressor::compressWithLevel($wrappedContent, $level);
            // Extract JS content back from script tags
            if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $compressed, $matches)) {
                return trim($matches[1]);
            }
            return $content;
        }

        return $content;
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
            if ($type === 'css') {
                header('Content-Type: text/css; charset=UTF-8');
            }
            elseif ($type === 'js') {
                header('Content-Type: application/javascript; charset=UTF-8');
            }

            header('Content-Length: ' . strlen($content));
            header('Cache-Control: private, max-age=' . (AssetLinkManager::getCacheConfig()['hours'] * 3600));
        }
    }

    /**
     * Inject global assets in optimal locations for proper loading order
     *
     * @param string $html The HTML content
     * @param array $globalAssets Array containing 'scripts' and 'stylesheets' keys
     * @param string $componentScripts Component scripts to inject after global scripts
     * @return string Modified HTML with global assets injected
     */
    private function injectGlobalAssets (string $html, array $globalAssets, string $componentScripts = ''): string
    {
        $globalStylesheets = $globalAssets['stylesheets'];
        $globalScripts = $globalAssets['scripts'];

        // If no global assets and no component scripts, return unchanged
        if (empty(trim($globalStylesheets)) && empty(trim($globalScripts)) && empty(trim($componentScripts))) {
            return $html;
        }

        // Inject global stylesheets in head for proper CSS cascading
        if (!empty(trim($globalStylesheets))) {
            if (preg_match('/<\/head>/i', $html)) {
                $html = preg_replace('/<\/head>/i', "{$globalStylesheets}</head>", $html, 1);
            }
            else {
                // If no head tag, put stylesheets at the beginning
                $html = "{$globalStylesheets}{$html}";
            }
        }

        // Combine global scripts and component scripts in proper order
        $allScripts = $globalScripts . $componentScripts;

        // Inject scripts at end of body (global scripts first, then component scripts)
        if (!empty(trim($allScripts))) {
            if (preg_match('/<\/body>/i', $html)) {
                $html = preg_replace('/<\/body>/i', "{$allScripts}</body>", $html, 1);
            }
            elseif (preg_match('/<\/html>/i', $html)) {
                // If no body tag, try to inject before closing html tag
                $html = preg_replace('/<\/html>/i', "{$allScripts}</html>", $html, 1);
            }
            else {
                // If neither body nor html tags exist, append at the end
                $html .= $allScripts;
            }
        }

        return $html;
    }
}
