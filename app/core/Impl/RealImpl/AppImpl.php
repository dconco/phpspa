<?php

namespace phpSPA\Core\Impl\RealImpl;

use phpSPA\Component;
use phpSPA\Http\Request;
use phpSPA\Http\Session;
use phpSPA\Core\Router\MapRoute;
use phpSPA\Core\Helper\CsrfManager;
use phpSPA\Core\Helper\SessionHandler;
use phpSPA\Core\Helper\CallableInspector;
use phpSPA\Core\Utils\Formatter\ComponentTagFormatter;

use const phpSPA\Core\Impl\Const\STATE_HANDLE;
use const phpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

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

	public function defaultTargetID(string $targetID): self
	{
		$this->defaultTargetID = $targetID;
		return $this;
	}

	public function defaultToCaseSensitive(): self
	{
		$this->defaultCaseSensitive = true;
		return $this;
	}

	public function attach(Component $component): self
	{
		$this->components[] = $component;
		return $this;
	}

	public function detach(Component $component): self
	{
		$key = array_search($component, $this->components, true);

		if ($key !== false) {
			unset($this->components[$key]);
		}
		return $this;
	}

	public function cors(array $data = []): self
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
					!in_array('*', $method)
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
							print_r(json_encode(['response' => $res]));
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
			static::format($componentOutput);

			// If the component has a script, execute it
			if (!empty($scripts)) {
				foreach ($scripts as $script) {
					if (is_callable($script)) {
						$scriptValue = call_user_func($script);

						if (is_string($scriptValue) && !empty($scriptValue)) {
							$componentOutput .=
								"\n<script>\n" . $scriptValue . "\n</script>\n";
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
								"<style>\n" .
								$styleValue .
								"\n</style>\n" .
								$componentOutput;
						}
					}
				}
			}

			if ($request->requestedWith() === 'PHPSPA_REQUEST') {
				$info = [
					'content' => base64_encode($componentOutput),
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

				print_r($this->renderedData);
				exit();
			}
		}
	}
}
