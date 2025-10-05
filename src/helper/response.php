<?php

/**
 * Global response helper function.
 *
 * @param mixed $data The response data.
 * @param int $statusCode The HTTP status code.
 * @param array $headers The response headers.
 * @return \phpSPA\Http\Response
 */
function response($data = null, int $statusCode = 200, array $headers = []): \phpSPA\Http\Response
{
    return new \phpSPA\Http\Response($data, $statusCode, $headers);
}