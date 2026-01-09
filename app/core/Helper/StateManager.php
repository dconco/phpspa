<?php

namespace PhpSPA\Core\Helper;

use Closure;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Core\Utils\Validate;

use const PhpSPA\Core\Impl\Const\STATE_HANDLE;
use const PhpSPA\Core\Impl\Const\UNDEFINED_STATE_VARIABLE;

/**
 * Class StateManager
 *
 * Provides methods and utilities for managing application state.
 * This class is responsible for handling state transitions, storing state data,
 * and providing access to state information throughout the application lifecycle.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @var string $stateKey
 * @var string $value
 */
class StateManager
{
	private string $stateKey;

	private mixed $value = UNDEFINED_STATE_VARIABLE;

	protected mixed $lastState;

	protected static bool $firstRender = false;

	private function setValue(mixed $v): void
	{
		$this->value = Validate::validate($v);
	}

	/**
	 * Initializes the state with a given key and a default value.
	 *
	 * @param string $stateKey The unique key used to identify the state.
	 * @param mixed $default The default value to initialize the state with.
	 */
	public function __construct(string $stateKey, $default)
	{
		$sessionData = SessionHandler::get(STATE_HANDLE);
		$request = new HttpRequest();
		$requestedWith = $request->requestedWith();

		if (!isset($sessionData[$stateKey]) && $requestedWith !== 'PHPSPA_REQUEST' && $requestedWith !== 'PHPSPA_REQUEST_SCRIPT') {
			self::$firstRender = true;
		}

		$this->stateKey = $stateKey;

		if (!isset($sessionData[$stateKey])) {
			$this->setValue($default);
			$sessionData[$stateKey] = $this->lastState = $this->value;
		}

		SessionHandler::set(STATE_HANDLE, $sessionData);
	}

	/**
	 * Invokes the object as a function.
	 *
	 * This magic method allows the object to be called as a function. Optionally accepts a value.
	 *
	 * @param mixed $value Optional value to be processed when the object is invoked.
	 * @return mixed The result of the invocation, depending on the implementation.
	 */
	public function __invoke()
	{
		$sessionData = SessionHandler::get(STATE_HANDLE);

		// If no argument was passed, return the current state value.
		if (\func_num_args() === 0) {
			return $sessionData[$this->stateKey] ?? $this->value;
		}
		// An argument was explicitly provided (even if it is falsy); treat it as the new state value.
		$value = func_get_arg(0);

		$this->lastState = $this->value;
		$this->setValue($value);
		$sessionData[$this->stateKey] = $this->value;
		SessionHandler::set(STATE_HANDLE, $sessionData);

		return $this->value;
	}

	/**
	 * Magic method to convert the object to its string representation.
	 *
	 * @return string The string representation of the object.
	 */
	public function __toString()
	{
		$sessionData = SessionHandler::get(STATE_HANDLE);

		$value = $sessionData[$this->stateKey] ?? $this->value;
		return \is_array($value) ? json_encode($value) : ($value === null ? '' : $value);
	}

	/**
	 * Applies the given closure to each item in the state, returning a new collection with the results.
	 *
	 * @param Closure $closure The closure to apply to each item.
	 * @return mixed The resulting collection after applying the closure.
	 */
	public function map(Closure $closure)
	{
		$sessionData = SessionHandler::get(STATE_HANDLE);
		$value = $sessionData[$this->stateKey] ?? $this->value;

		if (\is_array($value)) {
			$newValue = '';

			foreach ($value as $key => $item) {
				$newValue .= $closure($item, $key);
			}
			return $newValue;
		} else {
			throw new \RuntimeException(
				'map() can only be used on array state values.',
			);
		}
	}
}
