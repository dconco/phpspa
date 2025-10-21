<?php

namespace Component;

use PhpSPA\Client\PendingRequest;

/**
 * Creates a new fluent HTTP request.
 *
 * @param string $url The target URL.
 * @return PendingRequest
 */
function useFetch(string $url): PendingRequest
{
   // Pass only the URL to the constructor
   return new PendingRequest($url);
}