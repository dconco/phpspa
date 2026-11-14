<?php

const DISABLE_COMPRESSION_LOG = 1 << 0; // 1
const DISABLE_PRELOAD_LOG     = 1 << 1; // 2
const DISABLE_LIBRARY_LOG     = 1 << 2; // 4

/**
 * Sentinel value returned by App->run(true) for CORS preflight (OPTIONS) requests.
 * Headers are already sent; no body output is needed.
 */
const PREFLIGHT_HANDLED = '__phpspa_preflight_handled__';

require_once __DIR__ . '/../helper/scope.php';
require_once __DIR__ . '/../helper/fmt.php';
