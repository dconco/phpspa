<?php

namespace PhpSPA\Core\Interfaces;

use stdClass;

/**
 * Handles HTTP request data and provides methods to access request parameters,
 * headers, and other relevant information.
 *
 * This class is typically used to encapsulate all information about an incoming
 * HTTP request, such as GET, POST, and server variables.
 *
 * @author dconco <concodave@gmail.com>
 * @see https://phpspa.readthedocs.io/en/latest/20-request-handling
 */
interface RequestInterface
{
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
    public function __invoke(string $key, ?string $default = null): mixed;

    /**
     * Retrieves file data from the request by name.
     *
     * This method retrieves file data from the request. If a name is provided, it returns the file data for that specific
     * input field; otherwise, it returns all file data as an object.
     *
     * @param ?string $name The name of the file input.
     * @return ?array File data, or null if not set.
     */
    public function files(?string $name = null): ?array;

    /**
     * Validates the API key from the request headers.
     *
     * @param string $key The name of the header containing the API key. Default is 'Api-Key'.
     * @return bool Returns true if the API key is valid, false otherwise.
     */
    public function apiKey(string $key = 'Api-Key');

    /**
     * Retrieves authentication credentials from the request.
     *
     * This method retrieves the authentication credentials from the request, including both Basic Auth and Bearer token.
     * Returns an object with `basic` and `bearer` properties containing the respective credentials.
     *
     * @return stdClass The authentication credentials.
     */
    public function auth(): stdClass;

    /**
     * Parses and returns the query string parameters from the URL.
     *
     * This method parses the query string of the request URL and returns it as an object. If a name is specified,
     * it will return the specific query parameter value.
     *
     * @param ?string $name If specified, returns a specific query parameter by name.
     * @return mixed parsed query parameters or a specific parameter value.
     */
    public function urlQuery(?string $name = null);

    /**
     * Retrieves headers from the request.
     *
     * This method returns the headers sent with the HTTP request. If a specific header name is provided,
     * it will return the value of that header; otherwise, it returns all headers as an object.
     *
     * @param ?string $name The header name to retrieve. If omitted, returns all headers.
     * @return mixed The header, or a specific header value if `$name` is provided.
     */
    public function header(?string $name = null);

    /**
     * Retrieves the request body as an associative array.
     *
     * This method parses the raw POST body data and returns it as an associative array.
     * If a specific parameter is provided, it returns only that parameter's value.
     *
     * @param ?string $name The name of the body parameter to retrieve.
     * @return mixed The json data or null if parsing fails.
     */
    public function json(?string $name = null);

    /**
     * Retrieves a GET parameter by key.
     *
     * This method retrieves the value of a GET parameter by key. If no key is specified, it returns all GET parameters
     * as an object.
     *
     * @param ?string $key The key of the GET parameter.
     * @return mixed The parameter value, or null if not set.
     */
    public function get(?string $key = null);

    /**
     * Retrieves a POST parameter by key.
     *
     * This method retrieves the value of a POST parameter by key. If no key is specified, it returns all POST parameters
     * as an object.
     *
     * @param ?string $key The key of the POST parameter.
     * @return mixed The parameter value, or null if not set.
     */
    public function post(?string $key = null);

    /**
     * Retrieves a cookie value by key, or all cookies if no key is provided.
     *
     * This method retrieves a specific cookie by its key. If no key is provided, it returns all cookies as an object.
     *
     * @param ?string $key The key of the cookie.
     * @return mixed The cookie value, or null if not set.
     */
    public function cookie(?string $key = null);

    /**
     * Retrieves a session value by key, or all session data if no key is provided.
     *
     * This method retrieves a specific session value by key. If no key is specified, it returns all session data as an object.
     * It ensures that the session is started before accessing session data.
     *
     * @param ?string $key The key of the session value.
     * @return mixed The session value, or null if not set.
     */
    public function session(?string $key = null);

    /**
     * Retrieves the HTTP request method (GET, POST, PUT, DELETE, etc.).
     *
     * This method provides the HTTP request method used in the current request, e.g., "GET", "POST", "PUT", etc.
     *
     * @return string The HTTP method of the request.
     */
    public function method(): string;

    /**
     * Retrieves the IP address of the client making the request.
     *
     * This method returns the IP address of the client that initiated the request, taking into account possible proxies or load balancers.
     *
     * @return string The client's IP address.
     */
    public function ip(): string;

    /**
     * Checks if the current request is an AJAX request.
     *
     * This method determines if the current request was made via AJAX by checking the value of the `X-Requested-With` header.
     *
     * @return bool Returns true if the request is an AJAX request, otherwise false.
     */
    public function isAjax(): bool;

    /**
     * Retrieves the URL of the referring page.
     *
     * @return string|null The referrer URL, or null if not set.
     */
    public function referrer(): ?string;

    /**
     * Retrieves the server protocol used for the request.
     *
     * @return string|null The server protocol.
     */
    public function protocol(): ?string;

    /**
     * Checks if the request method matches a given method.
     *
     * @param string $method The HTTP method to check.
     * @return bool True if the request method matches, false otherwise.
     */
    public function isMethod(string $method): bool;

    /**
     * Checks if the request is made over HTTPS.
     *
     * @return bool True if the request is HTTPS, false otherwise.
     */
    public function isHttps(): bool;

    /**
     * Retrieves the time when the request was made.
     *
     * @return int The request time as a Unix timestamp.
     */
    public function requestTime(): int;

    /**
     * Returns the content type of the request.
     *
     * This method returns the value of the `Content-Type` header, which indicates the type of data being sent in the request.
     *
     * @return string|null The content type, or null if not set.
     */
    public function contentType(): ?string;

    /**
     * Returns the length of the request's body content.
     *
     * This method returns the value of the `Content-Length` header, which indicates the size of the request body in bytes.
     *
     * @return int|null The content length, or null if not set.
     */
    public function contentLength(): ?int;

    public function csrf();

    public function requestedWith();

    /**
     * Retrieves the request URI.
     *
     * @return string The request URI.
     */
    public function getUri(): string;
    
    /**
     * Determines if the current HTTP request originates from the same origin as the server.
     * 
     * This method implements same-origin policy checking by comparing the request's
     * origin with the server's host.
     * 
     * @see https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy
     * @return bool True if the request is from the same origin, false otherwise.
     */
    public function isSameOrigin(): bool;
}
