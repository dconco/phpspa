<?php

namespace PhpSPA\Http;

/**
 * Handles HTTP responses for API calls in a PHP application.
 * Provides methods to set response data, status codes, headers, and output JSON.
 *
 * @category HTTP
 * @author Samuel Paschalson <samuelpaschalson@gmail.com>
 * @copyright 2025 Samuel Paschalson
 * @see https://phpspa.tech/references/response/#response-api-examples
 */
class Response
{
    /**
     * @var mixed The response data to be encoded as JSON.
     */
    private $data = null;

    /**
     * @var int The HTTP status code.
     */
    private int $statusCode = 200;

    /**
     * @var array HTTP headers to be sent with the response.
     */
    private array $headers = [];

    /**
     * @var Request|null The request instance associated with the response.
     */
    private ?Request $fromRequest = null;

    /**
     * @var array HTTP status codes and their messages.
     */
    private array $statusMessages = [
        // 1xx: Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        // 2xx: Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3xx: Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx: Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // 5xx: Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Create a new response instance.
     *
     * @param mixed $data The response data.
     * @param int $statusCode The HTTP status code.
     * @param array $headers The response headers.
     */
    public function __construct($data = null, int $statusCode = 200, array $headers = [])
    {
        if ($data !== null) {
            $this->data = $data;
        }
        $this->statusCode = $statusCode;
        $this->headers = array_merge($this->headers, $headers);

        // Set default JSON header if not already set
        if (!isset($this->headers['Content-Type'])) {
            $this->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    /**
     * Create a new response instance.
     *
     * @param mixed $data The response data.
     * @param int $statusCode The HTTP status code.
     * @param array $headers The response headers.
     * @return static
     */
    public static function make($data = null, int $statusCode = 200, array $headers = []): self
    {
        return new static($data, $statusCode, $headers);
    }

    /**
     * Create a new JSON response instance.
     *
     * @param mixed $data The response data.
     * @param int $statusCode The HTTP status code.
     * @param array $headers The response headers.
     * @return static
     */
    public static function json($data = null, int $statusCode = 200, array $headers = []): self
    {
        $headers = array_merge($headers, ['Content-Type' => 'application/json; charset=utf-8']);
        return new static($data, $statusCode, $headers);
    }

    /**
     * Sets the response data.
     *
     * @param mixed $data The response data.
     * @return self
     */
    public function data($data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Sets the HTTP status code.
     *
     * @param int $code The HTTP status code.
     * @return self
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Adds a header to the response.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Sets the content type for the response.
     *
     * @param string $type The content type.
     * @param string $charset The charset (default: utf-8).
     * @return self
     */
    public function contentType(string $type, string $charset = 'utf-8'): self
    {
        return $this->header('Content-Type', "{$type}; charset={$charset}");
    }

    /**
     * Sets a success response (200 OK).
     *
     * @param mixed $data The response data.
     * @param string $message Optional success message.
     * @return self
     */
    public function success($data = null, string $message = 'Success'): self
    {
        $this->statusCode = 200;

        if ($data !== null) {
            $this->data = [
                'success' => true,
                'message' => $message,
                'data' => $data
            ];
        }

        return $this;
    }

    /**
     * Sets a created response (201 Created).
     *
     * @param mixed $data The response data.
     * @param string $message Optional success message.
     * @return self
     */
    public function created($data = null, string $message = 'Resource created successfully'): self
    {
        $this->statusCode = 201;

        if ($data !== null) {
            $this->data = [
                'success' => true,
                'message' => $message,
                'data' => $data
            ];
        }

        return $this;
    }

    /**
     * Sets an error response.
     *
     * @param string $message The error message.
     * @param int $code The HTTP status code.
     * @param mixed $details Additional error details.
     * @return self
     */
    public function error(string $message, int $code = 500, $details = null): self
    {
        $this->statusCode = $code;

        $this->data = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];

        if ($details !== null) {
            $this->data['errors'] = $details;
        }

        return $this;
    }

    /**
     * Sets a not found response (404 Not Found).
     *
     * @param string $message Optional error message.
     * @return self
     */
    public function notFound(string $message = 'Resource not found'): self
    {
        return $this->error($message, 404);
    }

    /**
     * Sets an unauthorized response (401 Unauthorized).
     *
     * @param string $message Optional error message.
     * @return self
     */
    public function unauthorized(string $message = 'Unauthorized'): self
    {
        return $this->error($message, 401);
    }

    /**
     * Sets a forbidden response (403 Forbidden).
     *
     * @param string $message Optional error message.
     * @return self
     */
    public function forbidden(string $message = 'Forbidden'): self
    {
        return $this->error($message, 403);
    }

    /**
     * Sets a validation error response (422 Unprocessable Entity).
     *
     * @param array $errors The validation errors.
     * @param string $message Optional error message.
     * @return self
     */
    public function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Sets a paginated response.
     *
     * @param mixed $items The paginated items.
     * @param int $total The total number of items.
     * @param int $perPage The number of items per page.
     * @param int $currentPage The current page number.
     * @param int $lastPage The last page number.
     * @return self
     */
    public function paginate($items, int $total, int $perPage, int $currentPage, int $lastPage): self
    {
        $this->data = [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total)
            ]
        ];

        return $this;
    }

    /**
     * Sends the response.
     *
     * @return void
     */
    public function send(): void
    {
        // Set the HTTP response code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Add status header if not already set
        if (!isset($this->headers['Status'])) {
            $statusMessage = $this->statusMessages[$this->statusCode] ?? 'Unknown Status';
            header("HTTP/1.1 {$this->statusCode} {$statusMessage}", true, $this->statusCode);
        }

        // If data is not null, encode and output as JSON
        if ($this->data !== null) {
            // Check if we should encode as JSON
            $contentType = $this->headers['Content-Type'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                // For non-JSON responses, output the data directly
                echo $this->data;
            }
        }

        // Exit to prevent further output
        exit;
    }

    /**
     * Convert the response to a string when echoed.
     *
     * @return string
     */
    public function __toString(): string
    {
        // Set headers (but we can't actually set headers in __toString)
        // This is mainly for getting the response as a string
        ob_start();

        if ($this->data !== null) {
            $contentType = $this->headers['Content-Type'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                echo $this->data;
            }
        }

        return ob_get_clean();
    }

    /**
     * Quickly send a JSON response.
     *
     * @param mixed $data The response data.
     * @param int $statusCode The HTTP status code.
     * @param array $headers The response headers.
     * @return void
     */
    public static function sendJson($data, int $statusCode = 200, array $headers = []): void
    {
        static::json($data, $statusCode, $headers)->send();
    }

    /**
     * Quickly send a success response.
     *
     * @param mixed $data The response data.
     * @param string $message Optional success message.
     * @return void
     */
    public static function sendSuccess($data, string $message = 'Success'): void
    {
        (new static())->success($data, $message)->send();
    }

    /**
     * Quickly send an error response.
     *
     * @param string $message The error message.
     * @param int $code The HTTP status code.
     * @param mixed $details Additional error details.
     * @return void
     */
    public static function sendError(string $message, int $code = 500, $details = null): void
    {
        (new static())->error($message, $code, $details)->send();
    }

    /**
     * Create a response instance from a request.
     *
     * @param Request $request The request instance.
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        $n = (new self());
        $n->fromRequest = $request;

        return $n;
    }

    public function caseSensitive(bool $caseSensitive = true): self
    {
        Router::setCaseSensitive($caseSensitive);
        return $this;
    }

    /**
     * Register a GET route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function get(string $uri, callable $callback): self
    {
        Router::get($uri, $callback, $this->fromRequest);
        return $this;
    }

    /**
     * Register a POST route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function post(string $uri, callable $callback): self
    {
        Router::post($uri, $callback, $this->fromRequest);
        return $this;
    }

    /**
     * Register a PUT route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function put(string $uri, callable $callback): self
    {
        Router::put($uri, $callback, $this->fromRequest);
        return $this;
    }

    /**
     * Register a DELETE route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function delete(string $uri, callable $callback): self
    {
        Router::delete($uri, $callback, $this->fromRequest);
        return $this;
    }

    /**
     * Register a PATCH route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function patch(string $uri, callable $callback): self
    {
        Router::patch($uri, $callback, $this->fromRequest);
        return $this;
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $uri The route URI.
     * @param callable $callback The route callback.
     * @return self
     */
    public function options(string $uri, callable $callback): self
    {
        Router::options($uri, $callback, $this->fromRequest);
        return $this;
    }
}
