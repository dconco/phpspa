<?php

namespace PhpSPA\Http;

/**
 * Redirects the client to the specified URL with the given HTTP status code.
 *
 * This function sends a redirect response to the client and terminates script execution.
 * It provides a clean way to perform HTTP redirects within the PhpSPA framework.
 *
 * @author dconco <me@dconco.tech>
 * @param string $url The URL to redirect to.
 * @param int $code The HTTP status code for the redirect (e.g., 301, 302).
 * @return void This function does not return; it terminates script execution.
 * @see https://phpspa.tech/requests/#redirects-session-management
 */
function Redirect(string $url, int $code = 0): void
{
    header("Location: $url", true, $code);
    exit();
}
