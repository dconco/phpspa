<?php

namespace PhpSPA\Core\Http;

use PhpSPA\Core\Utils\Validate;
use PhpSPA\Http\Request;
use PhpSPA\Http\Session;
use stdClass;

class HttpRequest implements Request
{
    use \PhpSPA\Core\Auth\Authentication;

    private array $tempData = [];

    public function __construct(readonly private array $params = [])
    {
    }

    public function __invoke(string $key, ?string $default = null): mixed
    {
        if (isset($_REQUEST[$key])) {
            return Validate::validate($_REQUEST[$key]);
        }

        return $default;
    }

    public function __set($name, $value)
    {
        $this->tempData[$name] = $value;
    }

    public function __get($name)
    {
        return $this->tempData[$name] ?? null;
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
        return Validate::validate(self::RequestApiKey($key));
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
        $parsed = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        $cl = new stdClass();

        if (!$parsed) {
            return $cl;
        }
        $parsed = mb_split('&', urldecode($parsed));

        $i = 0;
        while ($i < \count($parsed)) {
            $p = mb_split('=', $parsed[$i]);
            $key = $p[0];
            $value = $p[1] ? Validate::validate($p[1]) : null;

            $cl->$key = $value;
            $i++;
        }

        if (!$name) {
            return $cl;
        }
        return $cl->$name;
    }

    public function urlParams(?string $name = null)
    {
        if (!$name) {
            return Validate::validate($this->params);
        }
        return Validate::validate($this->params[$name] ?? null);
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
            return Validate::validate($headers);
        }
        return Validate::validate($headers[$name] ?? null);
    }

    public function json(?string $name = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if ($name !== null) {
            return Validate::validate($data[$name] ?? null);
        }
        return Validate::validate($data);
    }

    public function get(?string $key = null)
    {
        if (!$key) {
            return Validate::validate($_GET);
        }
        if (!isset($_GET[$key])) {
            return null;
        }
        return Validate::validate($_GET[$key] ?? null);
    }

    public function post(?string $key = null)
    {
        if (!$key) {
            return Validate::validate($_POST);
        }
        if (!isset($_POST[$key])) {
            return null;
        }

        $data = Validate::validate($_POST[$key]);
        return $data;
    }

    public function cookie(?string $key = null)
    {
        if (!$key) {
            return Validate::validate($_COOKIE);
        }
        return isset($_COOKIE[$key]) ? Validate::validate($_COOKIE[$key]) : null;
    }

    public function session(?string $key = null)
    {
        Session::start();

        if (!$key) {
            return Validate::validate($_SESSION);
        }

        return Validate::validate(Session::get($key));
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
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
        return $this->method() === strtoupper($method);
    }

    public function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||
            $_SERVER['SERVER_PORT'] == 443 || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function requestTime(): int
    {
        return (int) $_SERVER['REQUEST_TIME'] ?? 0;
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
        return urldecode(
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        );
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string from URI
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return rawurldecode($uri);
    }

    public function siteURL(): string {
        $path = $this->getUri();
        $scheme = $this->isHttps() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return "{$scheme}://{$host}{$path}";
    }

    public function origin(): string {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if (empty($origin) && !empty($referer)) {
            $parts = parse_url($referer);
            $origin = $parts['scheme'] . '://' . $parts['host'];
            
            // Optional: Add port if it exists
            if (isset($parts['port'])) {
                $origin .= ':' . $parts['port'];
            }
        }

        return $origin;
    }

    public function isSameOrigin(): bool {
        $host = parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST);
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        // Case 1: Browser explicitly sent Origin header
        if ($origin !== null) {
            $parsed = parse_url($origin, PHP_URL_HOST);
            return $parsed === $host;
        }

        // Case 2: No Origin -> verify Host matches server and path is relative
        $serverHost = $_SERVER['SERVER_NAME'] ?? '';
        return $host === $serverHost && str_starts_with($this->getUri(), '/');
    }
}
