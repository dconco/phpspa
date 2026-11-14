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
     * Construct a new CSRF manager for a specific form/action
     * To use, instantiate this class with a unique form name and optional metadata, then call generate() to create a token and verify() to validate it.
     * To change the csrf properties (e.g., token length, max tokens, token lifetime), you can extend this class and override the properties in your subclass.
     *
     * @param string $name Unique identifier for the form/action
     * @param mixed $metadata Optional metadata for the CSRF token, if its not null, it will be sent back during verification (instead of a boolean) and can be used to store additional information about the token (e.g., user ID, session ID, etc.)
     * @param string $sessionKey Session storage key for CSRF tokens
     */
    public function __construct(string $name, mixed $metadata = null, string $sessionKey = '_csrf_tokens');

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
     * @param mixed $matchMetadata Optional metadata to match against the stored metadata for the token. If provided, the verification will only succeed if the metadata matches.
     * @return mixed False if the token is invalid or expired, or if the metadata doesn't match. It will return the metadata if it is provided and matches. Else it will return true if the token is valid and not expired.
     * @see https://phpspa.tech/security/csrf-protection/#verifying-the-token
     */
    public function verify(bool $expireAfterUse = true, mixed $matchMetadata = null): mixed;

    /**
     * Verifies a given CSRF token against the stored token
     *
     * @param string $token Token to verify
     * @param bool $expireAfterUse Whether to remove token after successful verification
     * @param mixed $matchMetadata Optional metadata to match against the stored metadata for the token. If provided, the verification will only succeed if the metadata matches.
     * @return mixed False if the token is invalid or expired, or if the metadata doesn't match. It will return the metadata if it is provided and matches. Else it will return true if the token is valid and not expired.
     */
    public function verifyToken(string $token, bool $expireAfterUse = true, mixed $matchMetadata = null): mixed;

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
