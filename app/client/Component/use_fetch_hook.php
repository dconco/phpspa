<?php

namespace Component;

use PhpSPA\Core\Client\PendingRequest;

/**
 * Creates a new fluent HTTP request.
 *
 * @since v2.0.1
 * @param string $url The target URL.
 * @return PendingRequest
 */
function useFetch(string $url): PendingRequest
{
   // Pass only the URL to the constructor
   return new PendingRequest($url);
}