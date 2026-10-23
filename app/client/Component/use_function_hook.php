<?php

namespace Component;

use PhpSPA\Core\Helper\FunctionCaller;

/**
 * Creates a callable function handler for client-side execution.
 *
 * This function provides a bridge between server-side PHP functions and
 * client-side JavaScript execution within the PhpSPA framework, enabling
 * secure invocation of PHP functions from the frontend with CSRF protection.
 *
 * @package Component
 * @author dconco <me@dconco.tech>
 * @param callable|string $function The PHP function to make available for client-side calling.
 * @param bool $expireAfterUse Decides whether to expire this function instance after use, best for forms.
 * @param string|null $public_name Optional public name for the function, recommended to avoid exposing the actual function name to the client-side. If not provided, the callable name will be used.
 * @return FunctionCaller Handler object for secure function invocation.
 * @see https://phpspa.tech/hooks/use-function
 */
function useFunction (callable|string $function, bool $expireAfterUse = false, ?string $public_name = null): FunctionCaller
{
    return new FunctionCaller($function, $expireAfterUse, $public_name);
}
