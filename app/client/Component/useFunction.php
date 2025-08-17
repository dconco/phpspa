<?php

namespace Component;

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
	return new FunctionCaller($function);
}
