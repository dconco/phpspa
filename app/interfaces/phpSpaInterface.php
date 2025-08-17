<?php

namespace phpSPA\Interfaces;

use phpSPA\Core\Impl\RealImpl\AppImpl;
use phpSPA\Component;

interface phpSpaInterface
{
	/**
	 * App constructor.
	 *
	 * Initializes the App instance with the specified layout.
	 *
	 * @param callable $layout The name of the layout to be used by the application.
	 */
	public function __construct(callable $layout);

	/**
	 * Sets the target ID for the application.
	 *
	 * @param string $targetID The target ID to be set.
	 *
	 * @return AppImpl
	 */
	public function defaultTargetID(string $targetID): AppImpl;

	/**
	 * Sets the default behavior to case sensitive.
	 *
	 * Implementing this method should configure the system or component
	 * to treat relevant operations (such as string comparisons or lookups)
	 * as case sensitive by default.
	 *
	 * @return AppImpl
	 */
	public function defaultToCaseSensitive(): AppImpl;

	/**
	 * Configure CORS (Cross-Origin Resource Sharing) settings for the application.
	 *
	 * Loads default CORS configuration from the config file and optionally merges
	 * custom settings provided via the data parameter. Automatically removes
	 * duplicate values from array-type configuration options.
	 *
	 * @param array $data Optional custom CORS configuration to merge with defaults.
	 *                    Can include keys like:
	 *                    - 'allow_origins': array of allowed origin URLs
	 *                    - 'allow_methods': array of allowed HTTP methods
	 *                    - 'allow_headers': array of allowed request headers
	 *                    - 'allow_credentials': boolean for credential support
	 *                    - 'max_age': integer for preflight cache duration
	 *
	 * @return AppImpl Returns the current instance for method chaining
	 *
	 * @example
	 * // Use default CORS settings
	 * $instance->cors();
	 *
	 * @example
	 * // Override specific settings
	 * $instance->cors([
	 *     'allow_origins' => ['https://mydomain.com'],
	 *     'allow_headers' => ['Authorization', 'X-Custom-Header']
	 * ]);
	 *
	 * @example
	 * // Chain with other methods
	 * $instance->cors(['allow_credentials' => true])
	 *          ->someOtherMethod();
	 *
	 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5#cors
	 */
	public function cors(array $data = []): AppImpl;

	/**
	 * Attaches a component to the current object.
	 *
	 * @param Component $component The component instance to attach.
	 * @return AppImpl
	 */
	public function attach(Component $component): AppImpl;

	/**
	 * Detaches the specified component from the current context.
	 *
	 * @param Component $component The component instance to be detached.
	 * @return AppImpl
	 */
	public function detach(Component $component): AppImpl;

	/**
	 * Runs the application.
	 *
	 * This method is responsible for executing the main logic of the application,
	 * including routing, rendering components, and managing the application lifecycle.
	 *
	 * @return void
	 */
	public function run(): void;
}
