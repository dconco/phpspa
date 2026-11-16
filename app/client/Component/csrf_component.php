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
 * @author dconco <me@dconco.net>
 * @see https://phpspa.vercel.app/security/csrf-protection
 */
class Csrf
{
   public function __render(string $name): string
   {
      $csrf = new CsrfManager($name);
      return $csrf->getInput();
   }
}
