<?php

namespace Component;

/**
 *
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @since v1.1.5
 * @package phpSPA\Component
 */
class Csrf extends \phpSPA\Core\Helper\CsrfManager
{
	function __render(string $name)
	{
		$this->name = $name;
		return $this->getInput();
	}
}
