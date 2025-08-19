<?php

namespace Component;

/**
 * CSRF protection component for forms
 *
 * This class extends the CsrfManager to provide easy integration of CSRF
 * protection tokens into HTML forms within the phpSPA framework. It automatically
 * generates and validates CSRF tokens to prevent cross-site request forgery attacks.
 *
 * @package phpSPA\Component
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.1.5
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/5-csrf-protection/ CSRF Protection Documentation
 */
class Csrf extends \phpSPA\Core\Helper\CsrfManager
{
    public function __render(string $name)
    {
        $this->name = $name;
        return $this->getInput();
    }
}
