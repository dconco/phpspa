<?php

namespace phpSPA\Http;

use stdClass;

/**
 * Handles HTTP request data and provides methods to access request parameters,
 * headers, and other relevant information.
 *
 * This class is typically used to encapsulate all information about an incoming
 * HTTP request, such as GET, POST, and server variables.
 *
 * @category phpSPA\Http
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @see https://phpspa.readthedocs.io/en/latest/20-request-handling
 * @use \phpSPA\Core\Utils\Validate
 * @use \phpSPA\Core\Auth\Authentication
 */
class Request
{
	use \phpSPA\Core\Utils\Validate;
	use \phpSPA\Core\Auth\Authentication;

	/**
	 * Invokes the request object to retrieve a parameter value by key.
	 *
	 * Checks if the specified key exists in the request parameters ($_REQUEST).
	 * If found, validates and returns the associated value.
	 * If not found, returns the provided default value.
	 *
	 * @param string $key The key to look for in the request parameters.
	 * @param string|null $default The default value to return if the key does not exist. Defaults to null.
	 * @return mixed The validated value associated with the key, or the default value if the key is not present.
	 */
	public function __invoke(string $key, ?string $default = null): mixed
	{
		// Check if the key exists in the request parameters
		if (isset($_REQUEST[$key])) {
			// Validate and return the value associated with the key
			return $this->validate($_REQUEST[$key]);
		}

		// If the key does not exist, return the default value
		return $default;
	}

	/**
	 * Retrieves file data from the request by name.
	 *
	 * This method retrieves file data from the request. If a name is provided, it returns the file data for that specific
	 * input field; otherwise, it returns all file data as an object.
	 *
	 * @param ?string $name The name of the file input.
	 * @return ?array File data, or null if not set.
	 */
	public function files(?string $name = null): ?array
	{
		if (!$name) {
			return $_FILES;
		}
		if (!isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		return $_FILES[$name];
	}

	/**
	 * Validates the API key from the request headers.
	 *
	 * @param string $key The name of the header containing the API key. Default is 'Api-Key'.
	 * @return bool Returns true if the API key is valid, false otherwise.
	 */
	public function apiKey(string $key = 'Api-Key')
	{
		return $this->validate(self::RequestApiKey($key));
	}

	/**
	 * Retrieves authentication credentials from the request.
	 *
	 * This method retrieves the authentication credentials from the request, including both Basic Auth and Bearer token.
	 * Returns an object with `basic` and `bearer` properties containing the respective credentials.
	 *
	 * @return stdClass The authentication credentials.
	 */
	public function auth(): stdClass
	{
		$cl = new stdClass();
		$cl->basic = self::BasicAuthCredentials();
		$cl->bearer = self::BearerToken();

		return $cl;
	}

	/**
	 * Parses and returns the query string parameters from the URL.
	 *
	 * This method parses the query string of the request URL and returns it as an object. If a name is specified,
	 * it will return the specific query parameter value.
	 *
	 * @param ?string $name If specified, returns a specific query parameter by name.
	 * @return mixed parsed query parameters or a specific parameter value.
	 */
	public function urlQuery(?string $name = null)
	{
		if (php_sapi_name() == 'cli-server') {
			$parsed = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		} else {
			$parsed = parse_url(
				$_REQUEST['uri'] ?? $_SERVER['REQUEST_URI'],
				PHP_URL_QUERY,
			);
		}

		$cl = new stdClass();

		if (!$parsed) {
			return $cl;
		}
		$parsed = mb_split('&', urldecode($parsed));

		$i = 0;
		while ($i < count($parsed)) {
			$p = mb_split('=', $parsed[$i]);
			$key = $p[0];
			$value = $p[1] ? $this->validate($p[1]) : null;

			$cl->$key = $value;
			$i++;
		}

		if (!$name) {
			return $cl;
		}
		return $cl->$name;
	}

	/**
	 * Retrieves the request body as an associative array.
	 *
	 * This method parses the raw POST body data and returns it as an associative array.
	 * If a specific parameter is provided, it returns only that parameter's value.
	 *
	 * @param ?string $name The name of the body parameter to retrieve.
	 * @return mixed The json data or null if parsing fails.
	 */
	public function json(?string $name = null)
	{
		$data = json_decode(file_get_contents('php://input'), true);

		if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}

		if ($name !== null) {
			return $this->validate($data[$name]);
		}
		return $this->validate($data);
	}

	/**
	 * Retrieves a GET parameter by key.
	 *
	 * This method retrieves the value of a GET parameter by key. If no key is specified, it returns all GET parameters
	 * as an object.
	 *
	 * @param ?string $key The key of the GET parameter.
	 * @return mixed The parameter value, or null if not set.
	 */
	public function get(?string $key = null)
	{
		if (!$key) {
			return $this->validate($_GET);
		}
		if (!isset($_GET[$key])) {
			return null;
		}
		return $this->validate($_GET[$key]);
	}

	/**
	 * Retrieves a POST parameter by key.
	 *
	 * This method retrieves the value of a POST parameter by key. If no key is specified, it returns all POST parameters
	 * as an object.
	 *
	 * @param ?string $key The key of the POST parameter.
	 * @return mixed The parameter value, or null if not set.
	 */
	public function post(?string $key = null)
	{
		if (!$key) {
			return $this->validate($_POST);
		}
		if (!isset($_POST[$key])) {
			return null;
		}

		$data = $this->validate($_POST[$key]);
		return $data;
	}

	/**
	 * Retrieves a cookie value by key, or all cookies if no key is provided.
	 *
	 * This method retrieves a specific cookie by its key. If no key is provided, it returns all cookies as an object.
	 *
	 * @param ?string $key The key of the cookie.
	 * @return mixed The cookie value, or null if not set.
	 */
	public function cookie(?string $key = null)
	{
		if (!$key) {
			return (object) $this->validate($_COOKIE);
		}
		return isset($_COOKIE[$key]) ? $this->validate($_COOKIE[$key]) : null;
	}

	/**
	 * Retrieves a session value by key, or all session data if no key is provided.
	 *
	 * This method retrieves a specific session value by key. If no key is specified, it returns all session data as an object.
	 * It ensures that the session is started before accessing session data.
	 *
	 * @param ?string $key The key of the session value.
	 * @return mixed The session value, or null if not set.
	 */
	public function session(?string $key = null)
	{
		// Start the session if it's not already started
		session_status() === PHP_SESSION_NONE && session_start();

		// If no key is provided, return all session data as an object
		if (!$key) {
			return (object) $this->validate($_SESSION);
		}

		// If the session key exists, return its value; otherwise, return null
		return isset($_SESSION[$key]) ? $this->validate($_SESSION[$key]) : null;
	}

	/**
	 * Retrieves the HTTP request method (GET, POST, PUT, DELETE, etc.).
	 *
	 * This method provides the HTTP request method used in the current request, e.g., "GET", "POST", "PUT", etc.
	 *
	 * @return string The HTTP method of the request.
	 */
	public function method(): string
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Retrieves the IP address of the client making the request.
	 *
	 * This method returns the IP address of the client that initiated the request, taking into account possible proxies or load balancers.
	 *
	 * @return string The client's IP address.
	 */
	public function ip(): string
	{
		// Check for forwarded IP addresses from proxies or load balancers
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Checks if the current request is an AJAX request.
	 *
	 * This method determines if the current request was made via AJAX by checking the value of the `X-Requested-With` header.
	 *
	 * @return bool Returns true if the request is an AJAX request, otherwise false.
	 */
	public function isAjax(): bool
	{
		return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') ===
			'xmlhttprequest';
	}

	/**
	 * Retrieves the URL of the referring page.
	 *
	 * @return string|null The referrer URL, or null if not set.
	 */
	public function referrer(): ?string
	{
		return $_SERVER['HTTP_REFERER'] !== null
			? $_SERVER['HTTP_REFERER']
			: null;
	}

	/**
	 * Retrieves the server protocol used for the request.
	 *
	 * @return string The server protocol.
	 */
	public function protocol(): string
	{
		return $_SERVER['SERVER_PROTOCOL'];
	}
	
	/**
	 * Checks if the request method matches a given method.
	 *
	 * @param string $method The HTTP method to check.
	 * @return bool True if the request method matches, false otherwise.
	 */
	public function isMethod (string $method): bool
	{
		return strtoupper($this->method()) === strtoupper($method);
	}

	/**
	 * Checks if the request is made over HTTPS.
	 *
	 * @return bool True if the request is HTTPS, false otherwise.
	 */
	public function isHttps (): bool
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
		 $_SERVER['SERVER_PORT'] == 443;
	}

	/**
	 * Retrieves the time when the request was made.
	 *
	 * @return int The request time as a Unix timestamp.
	 */
	public function requestTime (): int
	{
		return (int) $_SERVER['REQUEST_TIME'];
	}

	/**
	 * Returns the content type of the request.
	 *
	 * This method returns the value of the `Content-Type` header, which indicates the type of data being sent in the request.
	 *
	 * @return string|null The content type, or null if not set.
	 */
	public function contentType (): ?string
	{
		return $this->header('Content-Type') ??
		 ($_SERVER['CONTENT_TYPE'] ?? null);
	}

	/**
	 * Returns the length of the request's body content.
	 *
	 * This method returns the value of the `Content-Length` header, which indicates the size of the request body in bytes.
	 *
	 * @return int|null The content length, or null if not set.
	 */
	public function contentLength (): ?int
	{
		return isset($_SERVER['CONTENT_LENGTH'])
		 ? (int) $_SERVER['CONTENT_LENGTH']
		 : null;
	}

	public function csrf ()
	{
		return $this->header('X-CSRF-TOKEN') ?: $this->header('X-Csrf-Token');
	}
}
