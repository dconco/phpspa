<?php

namespace phpSPA\Core\Helper;

use phpSPA\Http\Session;

/**
 * CSRF Token Manager - Generates, validates, and manages CSRF tokens for form security.
 *
 * This class provides methods to create, store, verify, and render CSRF tokens
 * as HTML inputs or meta tags. Tokens are stored in the session for validation.
 */
class CSRFTokenManager
{
	use \phpSPA\Core\Utils\Validate;

	/** @var string Session key for storing the CSRF token */
	private string $sessionKey = '_csrf_token';

	/** @var int Length of the generated token (in bytes) */
	private int $tokenLength = 32;

	/**
	 * Generates a cryptographically secure CSRF token and stores it in the session.
	 *
	 * @return string The generated token (hex-encoded)
	 * @throws \RuntimeException If random_bytes() fails to generate entropy
	 */
	public function generateToken(): string
	{
		Session::start();

		$token = bin2hex(random_bytes($this->tokenLength));
		Session::set($this->sessionKey, $token);

		return $token;
	}

	/**
	 * Retrieves the current CSRF token from session or generates a new one if none exists.
	 *
	 * @return string|null The stored token, or a newly generated one if empty
	 */
	public function getToken(): ?string
	{
		Session::start();
		return Session::get($this->sessionKey, $this->generateToken());
	}

	/**
	 * Verifies if a provided token matches the stored session token.
	 *
	 * @param string $token The token to verify
	 * @return bool True if tokens match (timing-safe comparison), false otherwise
	 */
	public function verifyToken(string $token): bool
	{
		Session::start();
		return Session::has($this->sessionKey)
			? hash_equals(Session::get($this->sessionKey), $token)
			: false;
	}

	/**
	 * Generates an HTML hidden input field containing the current CSRF token.
	 *
	 * @return string HTML input element (e.g., `<input type="hidden" name="csrf_token" value="abc123">`)
	 */
	public function getHiddenInput(): string
	{
		$token = $this->getToken();
		return sprintf(
			'<input type="hidden" name="csrf_token" value="%s">',
			(new static())->validate($token),
		);
	}

	/**
	 * Generates a meta tag containing the CSRF token for AJAX/XHR requests.
	 *
	 * @return string HTML meta tag (e.g., `<meta name="csrf-token" content="abc123">`)
	 */
	public function getMetaTag(): string
	{
		$token = $this->getToken();
		return sprintf(
			'<meta name="csrf-token" content="%s">',
			(new static())->validate($token),
		);
	}

	/**
	 * Regenerates the CSRF token (typically called after successful form submission).
	 *
	 * @return string The new token
	 */
	public function regenerateToken(): string
	{
		return $this->generateToken();
	}

	/**
	 * Clears the CSRF token from the session.
	 *
	 * @return never
	 */
	public function clearToken(): never
	{
		Session::start() && Session::remove($this->sessionKey);
	}
}
