<?php

namespace phpSPA\Core\Helper;

use Closure;
use phpSPA\Http\Session;
use const phpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

/**
 *
 * @package phpSPA\Core\Helper
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @var callable $function
 */
class FunctionCaller
{
	public string $token;

	function __construct(callable $function)
	{
		$funcName = $this->getCallableName($function);

		if (Session::has(CALL_FUNC_HANDLE)) {
			$token = Session::get(CALL_FUNC_HANDLE);
		} else {
			$token = bin2hex(random_bytes(length: 32));
			Session::set(CALL_FUNC_HANDLE, $token);
		}

		$this->token = base64_encode(json_encode([$funcName, $token]));
	}

	function __toString()
	{
		return "phpspa.__call('{$this->token}')";
	}

	function __invoke()
	{
		$arg = '';
		$args = func_get_args();

		foreach ($args as $value) {
			$arg .= ', ' . "'$value'";
		}

		return "phpspa.__call('{$this->token}'{$arg})";
	}

	private function getCallableName(callable $callable): string
	{
		if (is_string($callable)) {
			// Simple function name (e.g., 'strlen')
			return $callable;
		} elseif (is_array($callable)) {
			// Class method (e.g., [$object, 'method'] or ['ClassName', 'staticMethod'])
			if (is_object($callable[0])) {
				return get_class($callable[0]) . '::' . $callable[1];
			} else {
				return $callable[0] . '::' . $callable[1];
			}
		} elseif ($callable instanceof Closure) {
			// Anonymous function (closure)
			return 'Closure';
		} else {
			// Other cases (e.g., __invoke() magic method)
			return 'Unknown callable';
		}
	}
}
