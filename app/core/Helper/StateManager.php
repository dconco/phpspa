<?php

namespace phpSPA\Core\Helper;

use Closure;

use const phpSPA\Core\Impl\Const\STATE_HANDLE;
use const phpSPA\Core\Impl\Const\REGISTER_STATE_HANDLE;

/**
 * Class StateManager
 *
 * Provides methods and utilities for managing application state.
 * This class is responsible for handling state transitions, storing state data,
 * and providing access to state information throughout the application lifecycle.
 *
 * @package phpSPA\Core\Helper
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @var string $stateKey
 * @var mixed $value
 */
class StateManager
{
	private string $stateKey;
	private $value;

	/**
	 * Initializes the state with a given key and a default value.
	 *
	 * @param string $stateKey The unique key used to identify the state.
	 * @param mixed $default The default value to initialize the state with.
	 */
	public function __construct(string $stateKey, $default)
	{
		$this->value = $_SESSION[STATE_HANDLE . $stateKey] ?? $default;
		$this->stateKey = $stateKey;
		$_SESSION[STATE_HANDLE . $stateKey] =
			$_SESSION[STATE_HANDLE . $stateKey] ?? $default;

		$reg = unserialize($_SESSION[REGISTER_STATE_HANDLE] ?? serialize([]));
		if (!in_array($stateKey, $reg)) {
			array_push($reg, $stateKey);
			$_SESSION[REGISTER_STATE_HANDLE] = serialize($reg);
		}
	}

	/**
	 * Invokes the object as a function.
	 *
	 * This magic method allows the object to be called as a function. Optionally accepts a value.
	 *
	 * @param mixed $value Optional value to be processed when the object is invoked.
	 * @return mixed The result of the invocation, depending on the implementation.
	 */
	public function __invoke($value = null)
	{
		if (!$value) {
			return $_SESSION[STATE_HANDLE . $this->stateKey] ?? $this->value;
		}

		$this->value = $value;
		$_SESSION[STATE_HANDLE . $this->stateKey] = $value;
	}

	/**
	 * Magic method to convert the object to its string representation.
	 *
	 * @return string The string representation of the object.
	 */
	public function __toString()
	{
		$value = $_SESSION[STATE_HANDLE . $this->stateKey] ?? $this->value;
		return is_array($value) ? json_encode($value) : $value;
	}

	/**
	 * Applies the given closure to each item in the state, returning a new collection with the results.
	 *
	 * @param Closure $closure The closure to apply to each item.
	 * @return mixed The resulting collection after applying the closure.
	 */
	public function map(Closure $closure)
	{
		$value = $_SESSION[STATE_HANDLE . $this->stateKey] ?? $this->value;

		if (is_array($value)) {
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
