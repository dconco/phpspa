<?php

namespace PhpSPA\Core\Helper;

use PhpSPA\Http\Session;

/**
 * Advanced session data handling utilities
 *
 * This class provides enhanced session management capabilities with serialization
 * and base64 encoding for secure and efficient storage of complex data structures
 * within PHP sessions in the PhpSPA framework.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class SessionHandler
{
    public static function get(string $session): mixed
    {
        $default = serialize([]);
        $default = base64_encode($default);

        $sessionData = base64_decode(Session::get($session, $default));
        $sessionData = unserialize($sessionData);

        return $sessionData;
    }

    public static function set(string $session, mixed $value): void
    {
        $v = serialize($value);
        Session::set($session, base64_encode($v));
    }
}
