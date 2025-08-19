<?php

namespace Component;

use phpSPA\Core\Helper\FunctionCaller;

/**
 * Creates a callable function handler for client-side execution
 *
 * This function provides a bridge between server-side PHP functions and
 * client-side JavaScript execution within the phpSPA framework. It enables
 * secure invocation of PHP functions from the frontend with CSRF protection.
 *
 * @param callable $function The PHP function to make available for client-side calling
 * @package phpSPA\Component
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/2-php-js-integration/ PHP-JS Integration Documentation
 * @return FunctionCaller Handler object for secure function invocation
 */
function useFunction(callable $function): FunctionCaller
{
    return new FunctionCaller($function);
}
