<?php

namespace Component;

use PhpSPA\Core\Client\PendingRequest;

/**
 * Creates a new fluent HTTP request.
 *
 * @since v2.0.1
 * @package Component
 * @param string $url The target URL.
 * @return PendingRequest
 * @see https://phpspa.tech/references/hooks/use-fetch
 */
function useFetch(string $url): PendingRequest
{
   // Pass only the URL to the constructor
   return new PendingRequest($url);
}