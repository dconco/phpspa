<?php

namespace PhpSPA\Core\Helper;

use Closure;

use const PhpSPA\Core\Impl\Const\CALL_FUNC_HANDLE;

/**
 * Dynamic function calling utilities
 *
 * This class provides secure mechanisms for invoking PHP functions dynamically
 * within the PhpSPA framework. It includes CSRF protection and token-based
 * authentication for safe function execution in client-side contexts.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @see https://phpspa.tech/v1.1.5/2-php-js-integration/ PHP-JS Integration Documentation
 * @var callable $function
 */
class FunctionCaller
{
    public string $token;

    public function __construct(callable $function, bool $use_once)
    {
        $funcName = $this->getCallableName($function);
        $csrf = new CsrfManager($funcName, CALL_FUNC_HANDLE);

        $this->token = base64_encode(json_encode([$funcName, $csrf->getToken(), $use_once]));
    }

    public function __toString()
    {
        return "phpspa.__call('{$this->token}')";
    }

    public function __invoke()
    {
        $arg = '';
        $args = \func_get_args();

        foreach ($args as $value) {
            if (\is_array($value)) $value = json_encode($value);
            $arg .= ", $value";
        }

        return "phpspa.__call('{$this->token}'{$arg})";
    }

    private function getCallableName(callable $callable): string
    {
        if (\is_string($callable)) {
            // Simple function name (e.g., 'strlen')
            return $callable;
        } elseif (\is_array($callable)) {
            // Class method (e.g., [$object, 'method'] or ['ClassName', 'staticMethod'])
            if (\is_object($callable[0])) {
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
