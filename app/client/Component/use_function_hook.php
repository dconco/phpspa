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
 * @param callable $function The PHP function to make available for client-side calling.
 * @param bool $expireAfterUse Decides whether to expire this function instance after use, best for forms.
 * @return FunctionCaller Handler object for secure function invocation.
 * @see https://phpspa.tech/hooks/use-function
 */
function useFunction (callable $function, bool $expireAfterUse = false): FunctionCaller
{
    return new FunctionCaller($function, $expireAfterUse);
}
