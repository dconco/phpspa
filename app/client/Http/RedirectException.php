<?php

namespace PhpSPA\Http;

/**
 * Exception thrown when a redirect is requested.
 * 
 * This exception is caught by PhpSPA's internal dispatcher and handled appropriately.
 * The headers are sent before throwing, so the worker loop just needs to return.
 *
 * @author dconco <me@dconco.tech>
 */
class RedirectException extends \RuntimeException
{
    public function __construct(public readonly string $url, public $code = 302) {
        parent::__construct("Redirect to {$url}");
    }
}
