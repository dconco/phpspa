<?php

namespace phpSPA\Http;

/**
 * Redirects the client to the specified URL with the given HTTP status code.
 *
 * This function sends a redirect response to the client and terminates script execution.
 * It provides a clean way to perform HTTP redirects within the phpSPA framework.
 *
 * @param string $url The URL to redirect to.
 * @param int $code The HTTP status code for the redirect (e.g., 301, 302).
 * @package phpSPA\Http
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/6-redirect-funtion.md
 * @since v1.1.0
 * @return never This function does not return; it terminates script execution.
 */
function Redirect(string $url, int $code = 0): never
{
    header("Location: $url", true, $code);
    exit();
}
