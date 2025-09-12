<?php

namespace phpSPA\Core\Impl\RealImpl;

use phpSPA\App;
use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Http\Session;
use phpSPA\Core\Router\MapRoute;
use phpSPA\Compression\Compressor;
use phpSPA\Core\Helper\CsrfManager;
use phpSPA\Core\Helper\SessionHandler;
use phpSPA\Core\Helper\CallableInspector;
use phpSPA\Core\Helper\AssetLinkManager;
use phpSPA\Core\Utils\Formatter\ComponentTagFormatter;

use const phpSPA\Core\Impl\Const\STATE_HANDLE;
use const phpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

/**
 * Core application implementation class
 *
 * This abstract class provides the foundational implementation for the phpSPA
 * application framework. It handles layout management, component registration,
 * routing, and rendering logic that powers the single-page application experience.
 *
 * @package phpSPA\Core\Impl\RealImpl
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @var callable $layout
 * @var string $defaultTargetID
 * @var array $components
 * @var bool $defaultCaseSensitive
 * @staticvar string $request_uri
 * @var mixed $renderedData
 * @uses ComponentTagFormatter
 * @abstract
 */
abstract class AppImpl
{
    use ComponentTagFormatter;
    use \phpSPA\Core\Utils\Validate;

    /**
     * The layout of the application.
     *
     * @var callable $layout
     */
    protected $layout;

    /**
     * The default target ID where the application will render its content.
     *
     * @var string $defaultTargetID
     */
    private string $defaultTargetID;

    /**
     * Stores the list of application components.
     * Each component can be accessed and managed by the application core.
     * Typically used for dependency injection or service management.
     *
     * @var Component[] $components
     */
    private array $components = [];

    /**
     * Indicates whether the application should treat string comparisons as case sensitive.
     *
     * @var bool $defaultCaseSensitive Defaults to false, meaning string comparisons are case insensitive by default.
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

    public function defaultTargetID(string $targetID): App
    {
        $this->defaultTargetID = $targetID;
        return $this;
    }

    public function defaultToCaseSensitive(): App
    {
        $this->defaultCaseSensitive = true;
        return $this;
    }

    public function attach(Component $component): App
    {
        $this->components[] = $component;
        return $this;
    }

    public function detach(Component $component): App
    {
        $key = array_search($component, $this->components, true);

        if ($key !== false) {
            unset($this->components[$key]);
        }
        return $this;
    }

    public function cors(array $data = []): App
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

    public function run(): void
    {
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
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'options') {
            exit();
        }

        /**
         * Handle asset requests (CSS/JS files from session-based links)
         */
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
            } else {
                $router = (new MapRoute())->match($method, $route, $caseSensitive);
                if (!$router) {
                    continue;
                } // Skip if no match found
            }

            $request = new Request();

            $layoutOutput = (string) call_user_func($this->layout) ?? '';
            $componentOutput = '';

            if ($request->requestedWith() === 'PHPSPA_REQUEST') {
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
                                    'response' => base64_encode(json_encode($res)),
                                ]),
                            );
                        } else {
                            throw new \Exception('Invalid or Expired Token');
                        }
                    } catch (\Exception $e) {
                        print_r($e->getMessage());
                    }
                    exit();
                }
            } else {
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
            } elseif (CallableInspector::hasParam($componentFunction, 'path')) {
                $componentOutput = call_user_func(
                    $componentFunction,
                    path: $router['params'],
                );
            } elseif (CallableInspector::hasParam($componentFunction, 'request')) {
                $componentOutput = call_user_func(
                    $componentFunction,
                    request: $request,
                );
            } else {
                $componentOutput = call_user_func($componentFunction);
            }
            $componentOutput = static::format($componentOutput);

            // Generate session-based links for scripts and stylesheets instead of inline content
            $assetLinks = $this->generateAssetLinks($route, $scripts, $stylesheets);
            $componentOutput = $assetLinks['stylesheets'] . $componentOutput . $assetLinks['scripts'];

            if ($request->requestedWith() === 'PHPSPA_REQUEST') {
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
            } else {
                if ($title) {
                    $count = 0;
                    $layoutOutput = preg_replace_callback(
                        '/<title([^>]*)>.*?<\/title>/si',
                        function ($matches) use ($title) {
                            // $matches[1] contains any attributes inside the <title> tag
                            return '<title' . $matches[1] . '>' . $title . '</title>';
                        },
                        $layoutOutput,
                        -1,
                        $count
                    );

                    if ($count === 0) {
                        // If no <title> tag was found, add one inside the <head> section
                        $layoutOutput = preg_replace(
                            '/<head([^>]*)>/i',
                            '<head$1><title>' . $title . '</title>',
                            $layoutOutput,
                            1
                        );
                    }
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
                    function ($matches) use ($componentOutput, $tt) {
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
     * @param array $scripts Array of script callables
     * @param array $stylesheets Array of stylesheet callables
     * @return array Array with 'scripts' and 'stylesheets' HTML
     */
    private function generateAssetLinks($route, array $scripts, array $stylesheets): array
    {
        $result = ['scripts' => '', 'stylesheets' => ''];

        // Get the primary route for mapping purposes
        $primaryRoute = is_array($route) ? $route[0] : $route;

        // Generate stylesheet links
        if (!empty($stylesheets)) {
            foreach ($stylesheets as $index => $stylesheet) {
                if (is_callable($stylesheet)) {
                    $cssLink = AssetLinkManager::generateCssLink($primaryRoute, $index);
                    $result['stylesheets'] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$cssLink\" />\n";
                }
            }
        }

        // Generate script links
        if (!empty($scripts)) {
            foreach ($scripts as $index => $script) {
                if (is_callable($script)) {
                    $jsLink = AssetLinkManager::generateJsLink($primaryRoute, $index);
                    $result['scripts'] .= "\n<script type=\"text/javascript\" src=\"$jsLink\"></script>\n";
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
    private function serveAsset(array $assetInfo): void
    {
        // Find the component that matches the asset's route
        $component = $this->findComponentByRoute($assetInfo['componentRoute']);

        if ($component === null) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo "Asset not found";
            return;
        }

        // Get the asset content
        $content = $this->getAssetContent($component, $assetInfo);

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
    private function findComponentByRoute(string $targetRoute): ?Component
    {
        foreach ($this->components as $component) {
            $route = CallableInspector::getProperty($component, 'route');

            if (is_array($route)) {
                if (in_array($targetRoute, $route)) {
                    return $component;
                }
            } elseif ($route === $targetRoute) {
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
    private function getAssetContent(Component $component, array $assetInfo): ?string
    {
        if ($assetInfo['assetType'] === 'css') {
            $stylesheets = CallableInspector::getProperty($component, 'stylesheets');
            if (isset($stylesheets[$assetInfo['assetIndex']]) && is_callable($stylesheets[$assetInfo['assetIndex']])) {
                return call_user_func($stylesheets[$assetInfo['assetIndex']]);
            }
        } elseif ($assetInfo['assetType'] === 'js') {
            $scripts = CallableInspector::getProperty($component, 'scripts');
            if (isset($scripts[$assetInfo['assetIndex']]) && is_callable($scripts[$assetInfo['assetIndex']])) {
                return call_user_func($scripts[$assetInfo['assetIndex']]);
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
    private function compressAssetContent(string $content, string $type, int $level): string
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
        } elseif ($type === 'js') {
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
    private function setAssetHeaders(string $type, string $content): void
    {
        if (!headers_sent()) {
            if ($type === 'css') {
                header('Content-Type: text/css; charset=UTF-8');
            } elseif ($type === 'js') {
                header('Content-Type: application/javascript; charset=UTF-8');
            }

            header('Content-Length: ' . strlen($content));
            header('Cache-Control: private, max-age=' . (AssetLinkManager::getCacheConfig()['hours'] * 3600));
        }
    }
}
