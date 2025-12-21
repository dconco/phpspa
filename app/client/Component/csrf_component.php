<?php

namespace Component;

/**
 * Form CSRF protection component.
 *
 * Provides automatic CSRF token generation and validation for HTML forms
 * to prevent cross-site request forgery attacks.
 *
 * @package Component
 * @author dconco <me@dconco.tech>
 * @see https://phpspa.tech/security/csrf-protection
 */
class Csrf extends \PhpSPA\Core\Helper\CsrfManager
{
    public function __render(string $name)
    {
        $this->name = $name;
        return $this->getInput();
    }
}
