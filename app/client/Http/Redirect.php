<?php

namespace PhpSPA\Http;

/**
 * Redirects the client to the specified URL with the given HTTP status code.
 *
 * This function sends a redirect response and throws a RedirectException.
 * PhpSPA's dispatcher catches this exception internally and handles the response.
 * For FrankenPHP/worker mode compatibility, the exception bubbles up to the worker loop.
 *
 * @author dconco <me@dconco.tech>
 * @param string $url The URL to redirect to.
 * @param int $code The HTTP status code for the redirect (e.g., 301, 302).
 * @throws RedirectException Always thrown after sending headers.
 * @see https://phpspa.tech/requests/#redirects-session-management
 */
function Redirect(string $url, int $code = 302): never
{
    header("Location: $url", true, $code);
    throw new RedirectException($url, $code);
}
