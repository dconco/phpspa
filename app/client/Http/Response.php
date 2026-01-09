<?php

namespace PhpSPA\Http;

use PhpSPA\Compression\Compressor;
use PhpSPA\Core\Helper\FileHandler;

/**
 * Handles HTTP responses for API calls in a PHP application.
 * Provides methods to set response data, status codes, headers, and output JSON.
 *
 * @package HTTP
 * @author Samuel Paschalson <samuelpaschalson@gmail.com>
 * @copyright 2026 Samuel Paschalson
 * @see https://phpspa.tech/references/response/#response-api-examples
 */
class Response
{
   /**
    * @var array HTTP status codes and their messages.
    */
   private array $statusMessages = [
      // 1xx: Informational
      100 => 'Continue',
      101 => 'Switching Protocols',
      102 => 'Processing',
      103 => 'Early Hints',

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

   // --- 1xx: INFORMATIONAL ---
   public const StatusContinue                      = 100;
   public const StatusSwitchingProtocols            = 101;
   public const StatusProcessing                    = 102;
   public const StatusEarlyHints                    = 103;

   // --- 2xx: SUCCESS ---
   public const StatusOK                            = 200;
   public const StatusCreated                       = 201;
   public const StatusAccepted                      = 202;
   public const StatusNonAuthoritativeInfo          = 203;
   public const StatusNoContent                     = 204;
   public const StatusResetContent                  = 205;
   public const StatusPartialContent                = 206;
   public const StatusMulti                         = 207;
   public const StatusAlreadyReported               = 208;
   public const StatusIMUsed                        = 226;

   // --- 3xx: REDIRECTION ---
   public const StatusMultipleChoices               = 300;
   public const StatusMovedPermanently              = 301;
   public const StatusFound                         = 302;
   public const StatusSeeOther                      = 303;
   public const StatusNotModified                   = 304;
   public const StatusUseProxy                      = 305;
   public const StatusTemporaryRedirect             = 307;
   public const StatusPermanentRedirect             = 308;

   // --- 4xx: CLIENT ERROR ---
   public const StatusBadRequest                    = 400;
   public const StatusUnauthorized                  = 401;
   public const StatusPaymentRequired               = 402;
   public const StatusForbidden                     = 403;
   public const StatusNotFound                      = 404;
   public const StatusMethodNotAllowed              = 405;
   public const StatusNotAcceptable                 = 406;
   public const StatusProxyAuthRequired             = 407;
   public const StatusRequestTimeout                = 408;
   public const StatusConflict                      = 409;
   public const StatusGone                          = 410;
   public const StatusLengthRequired                = 411;
   public const StatusPreconditionFailed            = 412;
   public const StatusRequestEntityTooLarge         = 413;
   public const StatusRequestURITooLong             = 414;
   public const StatusUnsupportedMediaType          = 415;
   public const StatusRequestedRangeNotSatisfiable  = 416;
   public const StatusExpectationFailed             = 417;
   public const StatusTeapot                        = 418;
   public const StatusMisdirectedRequest            = 421;
   public const StatusUnprocessableEntity           = 422;
   public const StatusLocked                        = 423;
   public const StatusFailedDependency              = 424;
   public const StatusTooEarly                      = 425;
   public const StatusUpgradeRequired               = 426;
   public const StatusPreconditionRequired          = 428;
   public const StatusTooManyRequests               = 429;
   public const StatusRequestHeaderFieldsTooLarge   = 431;
   public const StatusUnavailableForLegalReasons    = 451;

   // --- 5xx: SERVER ERROR ---
   public const StatusInternalServerError           = 500;
   public const StatusNotImplemented                = 501;
   public const StatusBadGateway                    = 502;
   public const StatusServiceUnavailable            = 503;
   public const StatusGatewayTimeout                = 504;
   public const StatusHTTPVersionNotSupported       = 505;
   public const StatusVariantAlsoNegotiates         = 506;
   public const StatusInsufficientStorage           = 507;
   public const StatusLoopDetected             	  = 508;
   public const StatusNotExtended               	  = 510;
   public const StatusNetworkAuthenticationRequired = 511;

   /**
    * Create a new response instance.
    *
    * @param mixed $data The response data.
    * @param int $statusCode The HTTP status code.
    * @param array{
    *    Content-Type: string,
    *    Connection: string,
    *    Expires: string,
    *    Date: string,
    *    Accept: string,
    *    Cache-Control: string,
    *    Content-Length: int,
    * } $headers The response headers.
    */
   public function __construct(private $data = null, private int $statusCode = Response::StatusOK, private array $headers = [])
   {
      if (!isset($this->headers['Content-Type'])) {
         $this->contentType('application/json');
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
   public static function make($data = null, int $statusCode = Response::StatusOK, array $headers = []): self
   {
      return new static($data, $statusCode, $headers);
   }

   /**
    * Create a new JSON response.
    *
    * @param mixed $data The response data.
    * @return static
    */
   public function json($data = null): self
   {
      return $this
         ->contentType('application/json')
         ->data($data);
   }

   /**
    * Redirects the client to the specified URL with the given HTTP status code.
    *
    * This function sends a redirect response to the client and terminates script execution.
    * It provides a clean way to perform HTTP redirects within the PhpSPA framework.
    *
    * @param string $url The URL to redirect to.
    * @param int $code The HTTP status code for the redirect (e.g., 301, 302).
    * @return void This function does not return; it terminates script execution.
    * @see https://phpspa.tech/requests/#redirects-session-management
    */
   public function redirect(string $url, int $code = 0): void
   {
      header("Location: $url", true, $code);
      exit();
   }

   /**
    * Sends a file as the response.
    *
    * This method handles serving files, including PHP files which are executed.
    * It also automatically compresses text-based assets (HTML, CSS, JS).
    *
    * @param string $filePath The path to the file to send.
    * @return self
    * @throws \InvalidArgumentException If the file does not exist.
    */
   public function sendFile($filePath): self {
      if (!is_file($filePath)) {
         throw new \InvalidArgumentException("File does not exist: $filePath");
      }

      $fileType = FileHandler::fileType($filePath);

      if ($fileType === 'text/x-php') {
         $fileType = 'text/html';

         ob_start();
            require $filePath;
         $contents = ob_get_clean();
      }

      if (!isset($contents) || !$contents) {
         $contents = file_get_contents($filePath);
      }

      if ($fileType === 'text/html') {
         $contents = Compressor::compress($contents);
      } elseif ($fileType === 'text/css') {
         $level = Compressor::getLevel();
         $contents = Compressor::compressWithLevel($contents, $level, $fileType);
         $contents = Compressor::gzipCompress($contents, $fileType);
      }

      if ($fileType === 'application/json') {
         try {
            $content = json_decode($contents);
         } catch (\Exception $e) {
            $content = $contents;
         }
      }

      if (isset($content)) $contents = $content;

      return $this
         ->status(Response::StatusOK)
         ->contentType($fileType)
         ->data($contents);
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
      $this->status(code: Response::StatusOK);

      $this->data = [
         'success' => true,
         'message' => $message,
      ];

      if ($data !== null) {
         $this->data['data'] = $data;
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
      $this->status(code: Response::StatusCreated);

      $this->data = [
         'success' => true,
         'message' => $message,
      ];

      if ($data !== null) {
         $this->data['data'] = $data;
      }

      return $this;
   }

   /**
    * Sets an error response.
    *
    * @param string $message The error message.
    * @param mixed $details Additional error details.
    * @return self
    */
   public function error(string $message, $details = null): self
   {
      $this->data = [
         'success' => false,
         'message' => $message
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
      return $this->status(Response::StatusNotFound)->error($message);
   }

   /**
    * Sets an unauthorized response (401 Unauthorized).
    *
    * @param string $message Optional error message.
    * @return self
    */
   public function unauthorized(string $message = 'Unauthorized'): self
   {
      return $this->status(Response::StatusUnauthorized)->error($message);
   }

   /**
    * Sets a forbidden response (403 Forbidden).
    *
    * @param string $message Optional error message.
    * @return self
    */
   public function forbidden(string $message = 'Forbidden'): self
   {
      return $this->status(Response::StatusForbidden)->error($message);
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
      return $this->status(Response::StatusUnprocessableEntity)->error($message, $errors);
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
      if (!headers_sent()) {
         foreach ($this->headers as $name => $value) {
            header("$name: $value");
         }
      }

      // Add status header if not already set
      if (!isset($this->headers['Status'])) {
         $statusMessage = $this->statusMessages[$this->statusCode] ?? 'Unknown Status';
         header("HTTP/1.1 {$this->statusCode} {$statusMessage}", true, $this->statusCode);
      }

      // If data is not null, encode and output as JSON
      if ($this->data !== null) {
         // Check if we should encode as JSON
         $contentType = $this->headers['Content-Type'] ?? $this->headers['content-type'] ?? $this->headers['Content-type'] ?? '';
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
      if ($this->data !== null) {
         $contentType = strtolower($this->headers['Content-Type'] ?? $this->headers['content-type'] ?? $this->headers['Content-type'] ?? '');
         if (strpos($contentType, 'application/json') !== false) {
            return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
         } else {
            return $this->data;
         }
      }
      return '';
   }

   /**
    * Quickly send a JSON response.
    *
    * @param mixed $data The response data.
    * @param int $statusCode The HTTP status code.
    * @param array $headers The response headers.
    * @return void
    */
   public static function sendJson($data, int $statusCode = Response::StatusOK, array $headers = []): void
   {
      $static = new static();
      $static->headers = $headers;
      $static->status($statusCode);
      $static->json($data)->send();
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
      $static = new static();
      $static->success($data, $message)->send();
   }

   /**
    * Quickly send an error response.
    *
    * @param string $message The error message.
    * @param int $code The HTTP status code.
    * @param mixed $details Additional error details.
    * @return void
    */
   public static function sendError(string $message, int $code = Response::StatusInternalServerError, $details = null): void
   {
      $static = new static();
      $static->status($code)->error($message, $details)->send();
   }
}
