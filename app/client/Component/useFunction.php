<?php

namespace phpSPA\Component;

use phpSPA\Http\Session;
use phpSPA\Core\Helper\FunctionCaller;

/**
 *
 * @param callable $function
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @package phpSPA\Component
 * @return FunctionCaller
 */
function useFunction(callable $function): FunctionCaller
{
	return Session::start() && new FunctionCaller($function);
}
