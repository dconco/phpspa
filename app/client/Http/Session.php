<?php

namespace PhpSPA\Http;

use PhpSPA\Core\Utils\Validate;

/**
 * Session utility class for managing PHP sessions
 *
 * Provides static methods for common session operations with proper
 * error handling and validation. This class ensures secure and consistent
 * session management throughout the PhpSPA framework.
 *
 * @author dconco <me@dconco.tech>
 */
class Session
{

    /**
     * Check if a session is currently active
     *
     * @return bool True if session is active, false otherwise
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Start a new session if one is not already active
     *
     * @return bool True if session was started or already active, false on failure
     */
    public static function start(): bool
    {
        if (self::isActive()) {
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        ini_set('session.cookie_samesite', 'Strict');
        return session_start();
    }

    /**
     * Destroy the current session
     *
     * This method will unset all session variables, destroy the session,
     * and remove the session cookie if it exists.
     *
     * @return bool True if session was destroyed successfully, false otherwise
     */
    public static function destroy(): bool
    {
        if (!self::isActive()) {
            return true; // No session to destroy
        }

        // Unset all session variables
        unset($_SESSION);

        // Delete the session cookie if it exists
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        // Destroy the session
        return session_destroy();
    }

    /**
     * Get a session variable value
     *
     * @param string $key The session variable key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The session variable value or default
     */
    public static function get(string $key, $default = null)
    {
        Session::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session variable
     *
     * @param string $key The session variable key
     * @param mixed $value The value to store
     * @param bool $raw If to store raw value
     * @return bool True if value was set, false if session not active
     */
    public static function set(string $key, $value, bool $raw = false): bool
    {
        Session::start();

        $_SESSION[$key] = $raw ? $value : Validate::validate($value);
        return true;
    }

    /**
     * Remove one or more session variables
     *
     * @param string|array $key The session variable key(s) to remove
     * @return bool True if all variables were removed or didn't exist, false if session not active
     */
    public static function remove(string|array $key): bool
    {
        Session::start();

        if (\is_array($key)) {
            foreach ($key as $k) {
                if (\is_string($k)) {
                    unset($_SESSION[$k]);
                }
            }
        } else {
            unset($_SESSION[$key]);
        }

        return true;
    }

    /**
     * Check if a session variable exists
     *
     * @param string $key The session variable key
     * @return bool True if the key exists in the session, false otherwise
     */
    public static function has(string $key): bool
    {
        Session::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Regenerate session ID to prevent session fixation attacks
     *
     * @param bool $deleteOldSession Whether to delete the old session file
     * @return bool True if ID was regenerated successfully, false otherwise
     */
    public static function regenerateId(bool $deleteOldSession = true): bool
    {
        Session::start();
        return session_regenerate_id($deleteOldSession);
    }
}
