<?php

namespace Component;

use PhpSPA\Core\Helper\CsrfManager;

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
function Csrf (string $name) {
   $csrf = new CsrfManager($name);
   return $csrf->getInput();
}
