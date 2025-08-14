<?php

namespace phpSPA\Core\Helper;

use phpSPA\Http\Request;
use phpSPA\Http\Session;
use phpSPA\Core\Helper\SessionHandler;

/**
 * Secure CSRF (Cross-Site Request Forgery) protection component
 *
 * Provides generation, validation and automatic management of CSRF tokens
 * with support for multiple named forms and automatic token cleanup.
 *
 * @since v1.1.5
 * @copyright 2025 Dave Conco
 * @author dconco <concodave@gmail.com>
 * @category phpSPA\Core\Helper
 * use \phpSPA\Core\Utils\Validate
 */
class CsrfManager
{
    use \phpSPA\Core\Utils\Validate;

    /** @var string Unique identifier for the form/action */
    protected string $name;

    /** @var string Session storage key for CSRF tokens */
    private string $sessionKey;

    /** @var int Length of generated tokens in bytes (converted to hex) */
    private int $tokenLength = 32;

    /** @var int Maximum number of tokens to store simultaneously */
    private int $maxTokens = 10; // Limit stored tokens

    public function __construct(string $name, string $sessionKey = '_csrf_tokens')
    {
        $this->name = $name;
        $this->sessionKey = $sessionKey;
    }

    /**
     * Generates a new CSRF token for a specific form/action
     *
     * @return string Generated token (hex encoded)
     * @throws \RuntimeException If cryptographically secure random generation fails
     */
    public function generate(): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));
        $tokenData = [
            'token' => $token,
            'created' => time(),
            'form' => $this->name,
        ];

        // Add new token
        $this->registerForm($tokenData);

        // Clean up old tokens if too many
        $this->cleanupTokens();

        return $token;
    }

    /**
     * Verifies a submitted CSRF token against the stored token
     *
     * @param bool $expireAfterUse Whether to remove token after successful verification
     * @return bool True if token is valid and not expired, false otherwise
     */
    public function verify(bool $expireAfterUse = true): bool
    {
        if (empty($this->getSessionData()[$this->name])) {
            return false;
        }

        $storedTokenData = $this->getSessionData()[$this->name];

        $request = new Request();
        $token = $request($this->name, $request->csrf());

        $isValid = hash_equals($storedTokenData['token'], $token);

        // Check token age (expire after 1 hour)
        $maxAge = 3600; // 1 hour
        if ($isValid && time() - $storedTokenData['created'] > $maxAge) {
            $isValid = false;
        }

        // Remove token after successful use
        if ($isValid && $expireAfterUse) {
            $this->removeForm();
        }

        return $isValid;
    }

    /**
     * Verifies a given CSRF token against the stored token
     *
     * @param string $token Token to verify
     * @param bool $expireAfterUse Whether to remove token after successful verification
     * @return bool True if token is valid and not expired, false otherwise
     */
    public function verifyToken(string $token, bool $expireAfterUse = true): bool
    {
        if (empty($this->getSessionData()[$this->name])) {
            return false;
        }

        $storedTokenData = $this->getSessionData()[$this->name];
        $isValid = hash_equals($storedTokenData['token'], $token);

        // Check token age (expire after 1 hour)
        $maxAge = 3600; // 1 hour
        if ($isValid && time() - $storedTokenData['created'] > $maxAge) {
            $isValid = false;
        }

        // Remove token after successful use
        if ($isValid && $expireAfterUse) {
            $this->removeForm();
        }

        return $isValid;
    }

    /**
     * Retrieves the current CSRF token for a form, generating if needed
     *
     * @return string Existing or newly generated token
     */
    public function getToken(): string
    {
        if (empty($this->getSessionData()[$this->name])) {
            return $this->generate();
        }
        return $this->getSessionData()[$this->name]['token'];
    }

    /**
     * Generate hidden CSRF token fields for HTML forms
     *
     * @return string HTML containing two hidden inputs:
     *               - csrf_token: The generated token
     *               - csrf_form: The form identifier
     */
    public function getInput(): string
    {
        $token = $this->getToken();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $this->validate($this->name),
            $this->validate($token),
        );
    }

    /**
     * Registers a new form token in the session storage
     *
     * @param array $tokenData Token data including token string and timestamp
     * @return void
     */
    private function registerForm(array $tokenData): void
    {
        $sessionData = $this->getSessionData();
        $sessionData[$this->name] = $tokenData;
        $this->setSessionData($sessionData);
    }

    /**
     * Removes a form token from session storage
     *
     * @return void
     */
    private function removeForm(): void
    {
        $sessionData = $this->getSessionData();
        unset($sessionData[$this->name]);

        $this->setSessionData($sessionData);
    }

    /**
     * Cleans up expired tokens and enforces maximum token limit
     *
     * Removes:
     * - Tokens older than 1 hour (3600 seconds)
     * - Oldest tokens when total exceeds maxTokens limit
     */
    private function cleanupTokens(): void
    {
        if (!Session::has($this->sessionKey)) {
            return;
        }

        $tokens = $this->getSessionData();

        // Remove expired tokens (older than 1 hour)
        $maxAge = 3600;
        $currentTime = time();

        foreach ($tokens as $tokenData) {
            if ($currentTime - $tokenData['created'] > $maxAge) {
                $this->removeForm();
            }
        }

        // Limit total number of tokens
        if (count($this->getSessionData()) > $this->maxTokens) {
            $sessionData = $this->getSessionData();

            // Sort by creation time and remove oldest
            uasort($sessionData, function ($a, $b) {
                return $a['created'] - $b['created'];
            });

            $this->setSessionData(
                array_slice($sessionData, $this->maxTokens, null, true),
            );
        }
    }

    private function getSessionData(): array
    {
        $s = SessionHandler::get($this->sessionKey);
        return $s;
    }

    private function setSessionData(array $vv): void
    {
        SessionHandler::set($this->sessionKey, $vv);
    }
}
