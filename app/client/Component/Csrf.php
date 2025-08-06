<?php

namespace phpSPA\Component;

use phpSPA\Http\Session;

/**
 * Secure CSRF (Cross-Site Request Forgery) protection component
 *
 * Provides generation, validation and automatic management of CSRF tokens
 * with support for multiple named forms and automatic token cleanup.
 *
 * @category phpSPA\Component
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * use \phpSPA\Core\Utils\Validate
 */
class Csrf
{
	use \phpSPA\Core\Utils\Validate;

	/** @var string Session storage key for CSRF tokens */
	static string $sessionKey = '_csrf_tokens';

	/** @var int Length of generated tokens in bytes (converted to hex) */
	static int $tokenLength = 32;

	/** @var int Maximum number of tokens to store simultaneously */
	static int $maxTokens = 10; // Limit stored tokens

	/**
	 * Generates a new CSRF token for a specific form/action
	 *
	 * @param string $formName Unique identifier for the form/action
	 * @return string Generated token (hex encoded)
	 * @throws \RuntimeException If cryptographically secure random generation fails
	 */
	static function generate(string $formName): string
	{
		Session::start();

		$token = bin2hex(random_bytes(self::tokenLength));
		$tokenData = [
			'token' => $token,
			'created' => time(),
			'form' => $formName,
		];

		// Add new token
		self::registerForm($formName, $tokenData);

		// Clean up old tokens if too many
		self::cleanupTokens();

		return $token;
	}

	/**
	 * Verifies a submitted CSRF token against the stored token
	 *
	 * @param string $token Token to verify
	 * @param string $formName Name of the form being verified
	 * @param bool $expireAfterUse Whether to remove token after successful verification
	 * @return bool True if token is valid and not expired, false otherwise
	 */
	static function verify(
		string $token,
		string $formName,
		bool $expireAfterUse = true,
	): bool {
		Session::start();

		if (empty(Session::get(self::sessionKey, [])[$formName])) {
			return false;
		}

		$storedTokenData = Session::get(self::sessionKey)[$formName];
		$isValid = hash_equals($storedTokenData['token'], $token);

		// Check token age (expire after 1 hour)
		$maxAge = 3600; // 1 hour
		if ($isValid && time() - $storedTokenData['created'] > $maxAge) {
			$isValid = false;
		}

		// Remove token after successful use
		if ($isValid && $expireAfterUse) {
			self::removeForm($formName);
		}

		return $isValid;
	}

	/**
	 * Retrieves the current CSRF token for a form, generating if needed
	 *
	 * @param string $formName Name of the form
	 * @return string Existing or newly generated token
	 */
	static function getToken(string $formName): string
	{
		Session::start();

		if (empty(Session::get(self::sessionKey, [])[$formName])) {
			return self::generate($formName);
		}
		return Session::get(self::sessionKey)[$formName]['token'];
	}

	/**
	 * Renders hidden CSRF token fields for HTML forms
	 *
	 * @param string $name Form name/identifier
	 * @return string HTML containing two hidden inputs:
	 *               - csrf_token: The generated token
	 *               - csrf_form: The form identifier
	 */
	static function __render(string $name): string
	{
		$token = self::getToken($name);

		return sprintf(
			'<input type="hidden" name="csrf_token" value="%s"><input type="hidden" name="csrf_form" value="%s">',
			(new static())->validate($token),
			(new static())->validate($name),
		);
	}

	/**
	 * Registers a new form token in the session storage
	 *
	 * @param string $formName Name of the form to register
	 * @param array $tokenData Token data including token string and timestamp
	 * @return never
	 */
	private static function registerForm(
		string $formName,
		array $tokenData,
	): never {
		$sessionData = Session::get(self::sessionKey, []);
		$sessionData[$formName] = $tokenData;
		Session::set(self::sessionKey, $sessionData);
	}

	/**
	 * Removes a form token from session storage
	 *
	 * @param string $formName Name of the form to remove
	 * @return never
	 */
	private static function removeForm(string $formName): never
	{
		$sessionData = Session::get(self::sessionKey, []);
		unset($sessionData[$formName]);

		Session::set(self::sessionKey, $sessionData);
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
		if (!Session::has(self::sessionKey)) {
			return;
		}

		$tokens = Session::get(self::sessionKey);

		// Remove expired tokens (older than 1 hour)
		$maxAge = 3600;
		$currentTime = time();

		foreach ($tokens as $formName => $tokenData) {
			if ($currentTime - $tokenData['created'] > $maxAge) {
				self::removeForm($formName);
			}
		}

		// Limit total number of tokens
		if (count(Session::get(self::sessionKey)) > self::maxTokens) {
			$sessionData = Session::get(self::sessionKey);

			// Sort by creation time and remove oldest
			uasort($sessionData, function ($a, $b) {
				return $a['created'] - $b['created'];
			});

			Session::set(
				self::sessionKey,
				array_slice($sessionData - $this->maxTokens, null, true),
			);
		}
	}
}
