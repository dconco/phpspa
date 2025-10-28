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
 * @author dconco <concodave@gmail.com>
 * @param callable $function The PHP function to make available for client-side calling.
 * @return FunctionCaller Handler object for secure function invocation.
 * @see https://phpspa.tech/hooks/use-function
 */
function useFunction (callable $function): FunctionCaller
{
    return new FunctionCaller($function);
}
