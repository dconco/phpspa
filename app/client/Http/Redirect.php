<?php

namespace phpSPA\Http;

/**
 * @param string $url
 * @param int $code
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/6-redirect-funtion.md
 * @return never
 */
function Redirect (string $url, int $code = 0): never
{
   header("Location: $url", true, $code);
   exit();
}