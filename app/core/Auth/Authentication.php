<?php

namespace phpspa\Core\Auth;

/**
 * Authentication utilities for HTTP request handling
 *
 * This trait provides methods for extracting and processing various types of
 * authentication credentials from HTTP requests, including Basic Auth, Bearer tokens,
 * and other authentication schemes within the PhpSPA framework.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
trait Authentication
{
    // Static variable to store the authorization header
    private static ?string $authorizationHeader = null;

    /**
     * Retrieves the Authorization header from the request.
     *
     * This method fetches all headers from the request and stores the Authorization header
     * in a static property for subsequent use in authentication methods.
     *
     * @return void
     */
    private static function getAuthorizationHeader(): void
    {
        $headers = [];

        if (function_exists('\getallheaders')) {
            $headers = \getallheaders();
        } else if (function_exists('\apache_request_headers')) {
            $headers = \apache_request_headers();
        }

        self::$authorizationHeader =
            $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);
    }

    /**
     * Get Basic Authentication credentials from the Authorization header.
     *
     * This method checks the `Authorization` header for the Basic authentication scheme,
     * decodes the Base64 credentials, and splits them into the username and password.
     *
     * The username and password are returned as an associative array, or null if the
     * authorization header is not found or does not contain Basic credentials.
     *
     * @return ?array Returns an associative array with 'username' and 'password' or null if not found.
     */
    protected static function BasicAuthCredentials(): ?array
    {
        self::getAuthorizationHeader();

        // Check if the Authorization header is set and starts with "Basic"
        if (
            self::$authorizationHeader &&
            strpos(self::$authorizationHeader, 'Basic ') === 0
        ) {
            // Remove "Basic " from the header to extract base64 encoded credentials
            $base64Credentials = substr(self::$authorizationHeader, 6);

            // Decode the base64 credentials
            $decodedCredentials = base64_decode($base64Credentials, true);

            // Check if decoding was successful
            if ($decodedCredentials === false) {
                return null; // Return null if decoding fails
            }

            // Split the decoded string into username and password
            [$username, $password] = explode(':', $decodedCredentials, 2);

            // Return the credentials as an associative array
            return [
                'username' => trim(htmlspecialchars($username)),
                'password' => trim(htmlspecialchars($password)),
            ];
        }

        // Return null if no valid Basic Auth credentials are found
        return null;
    }

    /**
     * Get Bearer Token from the Authorization header.
     *
     * This method checks the `Authorization` header for the Bearer authentication scheme
     * and returns the token if found.
     *
     * @return ?string Returns the token as a string, or null if not found.
     */
    protected static function BearerToken(): ?string
    {
        self::getAuthorizationHeader();

        // Check if the Authorization header is set and starts with "Bearer"
        if (
            self::$authorizationHeader &&
            strpos(self::$authorizationHeader, 'Bearer ') === 0
        ) {
            // Extract the token by removing "Bearer " from the header
            return substr(self::$authorizationHeader, 7);
        }

        // Return null if no Bearer token is found
        return null;
    }

    /**
     * Retrieves the value of the specified API key from the request headers.
     *
     * This method attempts to find the API key in the following order:
     * 1. From the headers returned by `getallheaders()`.
     * 2. From the headers returned by `apache_request_headers()`.
     * 3. From the `$_SERVER` superglobal with the key prefixed by "HTTP_".
     *
     * @param string $key The name of the API key to retrieve.
     * @return string|null The value of the API key if found, or null if not found.
     */
    protected static function RequestApiKey(string $key)
    {
        if (function_exists('\getallheaders')) {
            $val = \getallheaders()[$key] ?? null;
            if ($val) return $val;
        }

        if (function_exists('\apache_request_headers')) {
            $val = \apache_request_headers()[$key] ?? null;
            if ($val) return $val;
        }

        return $_SERVER["HTTP_$key"] ?? null;
    }
}
