<?php

namespace Component;

/**
 * Form CSRF protection component.
 *
 * Provides automatic CSRF token generation and validation for HTML forms
 * to prevent cross-site request forgery attacks.
 *
 * @author dconco <concodave@gmail.com>
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/5-csrf-protection CSRF Protection Documentation
 */
class Csrf extends \phpSPA\Core\Helper\CsrfManager
{
    public function __render(string $name)
    {
        $this->name = $name;
        return $this->getInput();
    }
}
