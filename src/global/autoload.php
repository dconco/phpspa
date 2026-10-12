<?php

const DISABLE_COMPRESSION_LOG = 1 << 0; // 1
const DISABLE_PRELOAD_LOG     = 1 << 1; // 2
const DISABLE_LIBRARY_LOG     = 1 << 2; // 4

require_once __DIR__ . '/../helper/scope.php';
require_once __DIR__ . '/../helper/fmt.php';