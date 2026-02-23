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
    public const int LEVEL_NONE = 0;
    public const int LEVEL_AUTO = 1;
    public const int LEVEL_BASIC = 2;
    public const int LEVEL_AGGRESSIVE = 3;
    public const int LEVEL_EXTREME = 4;

    /**
     * Environment presets
     */
    public const string ENV_STAGING = 'staging';
    public const string ENV_DEVELOPMENT = 'development';
    public const string ENV_PRODUCTION = 'production';
}
