<?php

namespace PhpSPA\Core\Interfaces;

/**
 * Secure CSRF (Cross-Site Request Forgery) protection component
 *
 * Provides generation, validation and automatic management of CSRF tokens
 * with support for multiple named forms and automatic token cleanup.
 *
 * @since v1.1.5
 * @copyright 2026 Dave Conco
 * @author dconco <me@dconco.tech>
 * @see https://phpspa.tech/security/csrf-protection
 */
interface CsrfManagerInterface
{
    /**
     * Generates a new CSRF token for a specific form/action
     *
     * @return string Generated token (hex encoded)
     * @throws \RuntimeException If cryptographically secure random generation fails
     */
    public function generate(): string;

    /**
     * Verifies a submitted CSRF token against the stored token
     *
     * @param bool $expireAfterUse Whether to remove token after successful verification
     * @return bool True if token is valid and not expired, false otherwise
     * @see https://phpspa.tech/security/csrf-protection/#verifying-the-token
     */
    public function verify(bool $expireAfterUse = true): bool;

    /**
     * Verifies a given CSRF token against the stored token
     *
     * @param string $token Token to verify
     * @param bool $expireAfterUse Whether to remove token after successful verification
     * @return bool True if token is valid and not expired, false otherwise
     */
    public function verifyToken(string $token, bool $expireAfterUse = true): bool;

    /**
     * Retrieves the current CSRF token for a form, generating if needed
     *
     * @return string Existing or newly generated token
     */
    public function getToken(): string;

    /**
     * Generate hidden CSRF token fields for HTML forms
     *
     * @return string HTML containing two hidden inputs:
     *               - csrf_token: The generated token
     *               - csrf_form: The form identifier
     */
    public function getInput(): string;
}
