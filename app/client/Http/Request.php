<?php

namespace PhpSPA\Http;

use stdClass;
use PhpSPA\Core\Interfaces\RequestInterface;


/**
 * Handles HTTP request data and provides methods to access request parameters,
 * headers, and other relevant information.
 *
 * This class is typically used to encapsulate all information about an incoming
 * HTTP request, such as GET, POST, and server variables.
 *
 * @author dconco <concodave@gmail.com>
 * @see https://phpspa.vercel.app/requests/request-object
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
 */
class Request implements RequestInterface
{
    use \PhpSPA\Core\Utils\Validate;
    use \PhpSPA\Core\Auth\Authentication;

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

    public function apiKey(string $key = 'Api-Key')
    {
        return $this->validate(self::RequestApiKey($key));
    }

    public function auth(): stdClass
    {
        $cl = new stdClass();
        $cl->basic = self::BasicAuthCredentials();
        $cl->bearer = self::BearerToken();

        return $cl;
    }

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

    public function header(?string $name = null)
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            // CLI fallback - construct headers from $_SERVER
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }

        if (!$name) {
            return $this->validate($headers);
        }
        if (isset($headers[$name])) {
            return $this->validate($headers[$name]);
        } else {
            return null;
        }
    }

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

    public function cookie(?string $key = null)
    {
        if (!$key) {
            return (object) $this->validate($_COOKIE);
        }
        return isset($_COOKIE[$key]) ? $this->validate($_COOKIE[$key]) : null;
    }

    public function session(?string $key = null)
    {
        // Start the session if it's not already started
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // If no key is provided, return all session data as an object
        if (!$key) {
            return (object) $this->validate($_SESSION);
        }

        // If the session key exists, return its value; otherwise, return null
        return isset($_SESSION[$key]) ? $this->validate($_SESSION[$key]) : null;
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function ip(): string
    {
        // Check for forwarded IP addresses from proxies or load balancers
        if (
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) ||
            $this->header('X-Forwarded-For')
        ) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'] ?:
                $this->header('X-Forwarded-For');
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function isAjax(): bool
    {
        return strtolower(
            $_SERVER['HTTP_X_REQUESTED_WITH'] ?? $this->header('X-Requested-With'),
        ) === 'xmlhttprequest';
    }

    public function referrer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? $this->header('Referer') !== null
            ? $_SERVER['HTTP_REFERER']
            : null;
    }

    public function protocol(): ?string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? null;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method()) === strtoupper($method);
    }

    public function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            $_SERVER['SERVER_PORT'] == 443;
    }

    public function requestTime(): int
    {
        return (int) $_SERVER['REQUEST_TIME'];
    }

    public function contentType(): ?string
    {
        return $this->header('Content-Type') ??
            ($_SERVER['CONTENT_TYPE'] ?? null);
    }

    public function contentLength(): ?int
    {
        return isset($_SERVER['CONTENT_LENGTH'])
            ? (int) $_SERVER['CONTENT_LENGTH']
            : null;
    }

    public function csrf()
    {
        return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->header('X-CSRF-TOKEN') ?:
            $this->header('X-Csrf-Token');
    }

    public function requestedWith()
    {
        return $_SERVER['HTTP_X_REQUESTED_WITH'] ??
            $this->header('X-Requested-With');
    }

    public function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string from URI
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return rawurldecode($uri);
    }
    
    public function isSameOrigin(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        // Case 1: Browser explicitly sent Origin
        if ($origin !== null) {
            $parsed = parse_url($origin, PHP_URL_HOST);
            return $parsed === $host;
        }

        // Case 2: No Origin -> assume same-origin if Host matches server
        $serverHost = $_SERVER['SERVER_NAME'] ?? '';
        return $host === $serverHost;
    }
}
