<?php

namespace phpSPA\Core\Impl\RealImpl;

use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Http\Session;
use phpSPA\Core\Router\MapRoute;
use phpSPA\Core\Helper\CallableInspector;
use phpSPA\Core\Utils\Formatter\ComponentTagFormatter;

use const phpSPA\Core\Impl\Const\STATE_HANDLE;
use const phpSPA\Core\Impl\Const\REGISTER_STATE_HANDLE;

/**
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @var callable $layout
 * @var string $defaultTargetID
 * @var array $components
 * @var bool $defaultCaseSensitive
 * @staticvar string $request_uri
 * @var mixed $renderedData
 * @use ComponentTagFormatter
 * @abstract
 */
abstract class AppImpl
{
	use ComponentTagFormatter;

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

	public function defaultTargetID(string $targetID): void
	{
		$this->defaultTargetID = $targetID;
	}

	public function defaultToCaseSensitive(): void
	{
		$this->defaultCaseSensitive = true;
	}

	public function attach(Component $component): void
	{
		$this->components[] = $component;
	}

	public function detach(Component $component): void
	{
		$key = array_search($component, $this->components, true);

		if ($key !== false) {
			unset($this->components[$key]);
		}
	}

	public function cors(array $data)
	{
		$this->cors = $data;
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
					!in_array('*', self::$method)
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

			$layoutOutput = call_user_func($this->layout);
			$componentOutput = '';

			if (strtolower($request->requestedWith() ?: '') === 'phpspa_request') {
				$body = json_decode($request->get('phpspa_body') ?? '', true);

				if ($request->get('phpspa_body') !== null &&
					json_last_error() === JSON_ERROR_NONE
				) {
					if (!empty($body['stateKey']) && !empty($body['value'])) {
						Session::start();

						$reg = unserialize(
							Session::get(REGISTER_STATE_HANDLE, serialize([])),
						);
						if (in_array($body['stateKey'], $reg)) {
							Session::set(
								STATE_HANDLE . $body['stateKey'],
								serialize($body['value']),
							);
						}
					}
				}

				$body = json_decode(
					$request->get('phpspa_call_php_function') ?? '',
					true,
				);
				if (
					$request->get('phpspa_call_php_function') !== null &&
					json_last_error() === JSON_ERROR_NONE
				) {
					try {
						$res = call_user_func_array(
							$body['functionName'],
							$body['args'],
						);
						print_r(json_encode(['response' => $res]));
					} catch (\Exception $e) {
						print_r($e->getMessage());
					}
					exit();
				}
			} else {
				Session::start();

				$reg = unserialize(
					Session::get(REGISTER_STATE_HANDLE, serialize([])),
				);
				$keys = array_map(fn($key) => STATE_HANDLE . $key, $reg);
				Session::remove($keys);
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
			static::format($componentOutput);

			// If the component has a script, execute it
			if (!empty($scripts)) {
				foreach ($scripts as $script) {
					if (is_callable($script)) {
						$scriptValue = call_user_func($script);

						if (is_string($scriptValue) && !empty($scriptValue)) {
							$componentOutput .=
								"\n<script data-type=\"phpspa/script\">\n" .
								$scriptValue .
								"\n</script>\n";
						}
					}
				}
			}

			// If the component has a style, execute it
			if (!empty($stylesheets)) {
				foreach ($stylesheets as $style) {
					if (is_callable($style)) {
						$styleValue = call_user_func($style);

						if (is_string($styleValue) && !empty($styleValue)) {
							$componentOutput =
								"<style data-type=\"phpspa/css\">\n" .
								$styleValue .
								"\n</style>\n" .
								$componentOutput;
						}
					}
				}
			}

			if (strtolower($request->requestedWith() ?: '') === 'phpspa_request') {
				$info = [
					'content' => $componentOutput,
					'title' => $title,
					'targetID' => $targetID,
				];
				if ($reloadTime > 0) {
					$info['reloadTime'] = $reloadTime;
				}
				print_r(json_encode($info));
				exit();
			} else {
				if ($title) {
					$layoutOutput = preg_replace_callback(
						'/<title([^>]*)>.*?<\/title>/si',
						function ($matches) use ($title) {
							// $matches[1] contains any attributes inside the <title> tag
							return '<title' . $matches[1] . '>' . $title . '</title>';
						},
						$layoutOutput,
					);
				}
				$tt = '';
				static::format($layoutOutput);

				if ($reloadTime > 0) {
					$tt = " phpspa-reload-time=\"$reloadTime\"";
				}

				$this->renderedData = str_replace(
					'__CONTENT__',
					"\n<div data-phpspa-target$tt>" . $componentOutput . "</div>\n",
					$layoutOutput,
				);

				print_r($this->renderedData);
				exit();
			}
		}
	}
}
