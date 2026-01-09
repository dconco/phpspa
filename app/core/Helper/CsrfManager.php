<?php

namespace PhpSPA\Core\Helper;

use PhpSPA\Http\Session;
use PhpSPA\Core\Utils\Validate;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Core\Helper\SessionHandler;
use PhpSPA\Core\Interfaces\CsrfManagerInterface;

class CsrfManager implements CsrfManagerInterface
{

    /** @var string Unique identifier for the form/action */
    protected string $name;

    /** @var string Session storage key for CSRF tokens */
    private string $sessionKey;

    /** @var int Length of generated tokens in bytes (converted to hex) */
    private int $tokenLength = 32;

    /** @var int Maximum number of tokens to store simultaneously */
    protected int $maxTokens = 10; // Limit stored tokens

    /** @var int Maximum age of tokens to last */
    protected int $tokenLifetime = 3600; // Token last for 1 hour

    public function __construct(
        string $name,
        string $sessionKey = '_csrf_tokens',
    ) {
        $this->name = $name;
        $this->sessionKey = $sessionKey;
    }

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

    public function verify(bool $expireAfterUse = true): bool
    {
        if (empty($this->getSessionData()[$this->name])) {
            return false;
        }

        $storedTokenData = $this->getSessionData()[$this->name];

        $request = new HttpRequest();
        $token = $request($this->name, $request->csrf());

        $isValid = hash_equals($storedTokenData['token'], $token);

        // Check token age (expire after 1 hour)
        $maxAge = $this->tokenLifetime; // 1 hour

        if ($isValid && time() - $storedTokenData['created'] > $maxAge) {
            $isValid = false;
        }

        // Remove token after successful use
        if ($isValid && $expireAfterUse) {
            $this->removeForm();
        }

        return $isValid;
    }

    public function verifyToken(string $token, bool $expireAfterUse = true): bool
    {
        if (empty($this->getSessionData()[$this->name])) {
            return false;
        }

        $storedTokenData = $this->getSessionData()[$this->name];
        $isValid = hash_equals($storedTokenData['token'], $token);

        // Check token age (expire after 1 hour)
        $maxAge = $this->tokenLifetime; // 1 hour

        if ($isValid && time() - $storedTokenData['created'] > $maxAge) {
            $isValid = false;
        }

        // Remove token after successful use
        if ($isValid && $expireAfterUse) {
            $this->removeForm();
        }

        return $isValid;
    }

    public function getToken(): string
    {
        if (empty($this->getSessionData()[$this->name])) {
            return $this->generate();
        }
        return $this->getSessionData()[$this->name]['token'];
    }

    public function getInput(): string
    {
        $token = $this->getToken();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            Validate::validate($this->name),
            Validate::validate($token),
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
        $maxAge = $this->tokenLifetime;
        $currentTime = time();

        foreach ($tokens as $tokenData) {
            if ($currentTime - $tokenData['created'] > $maxAge) {
                $this->removeForm();
            }
        }

        // Limit total number of tokens
        if (\count($this->getSessionData()) > $this->maxTokens) {
            $sessionData = $this->getSessionData();

            // Sort by creation time and remove oldest
            uasort($sessionData, function ($a, $b) {
                return $a['created'] - $b['created'];
            });

            $this->setSessionData(
                \array_slice($sessionData, $this->maxTokens, null, true),
            );
        }
    }

    private function getSessionData(): array
    {
        return SessionHandler::get($this->sessionKey);
    }

    private function setSessionData(array $vv): void
    {
        SessionHandler::set($this->sessionKey, $vv);
    }
}
