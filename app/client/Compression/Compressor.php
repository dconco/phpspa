<?php

namespace PhpSPA\Compression;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for PhpSPA
 * to reduce payload sizes and improve performance. This class implements
 * various compression levels and environment-specific optimizations.
 *
 * @package Compression
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @see https://phpspa.tech/performance/html-compression/ Compression System Documentation
 */
class Compressor
{
    use \PhpSPA\Core\Utils\HtmlCompressor;

    /**
     * Compression levels
     */
    public const LEVEL_NONE = 0;
    public const LEVEL_AUTO = 1;
    public const LEVEL_BASIC = 2;
    public const LEVEL_AGGRESSIVE = 3;
    public const LEVEL_EXTREME = 4;

    /**
     * Environment presets
     */
    public const ENV_STAGING = 'staging';
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_PRODUCTION = 'production';
}
