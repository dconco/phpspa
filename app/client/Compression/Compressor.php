<?php

namespace phpSPA\Compression;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for phpSPA
 * to reduce payload sizes and improve performance. This class implements
 * various compression levels and environment-specific optimizations.
 *
 * @package phpSPA\Compression
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @see https://phpspa.readthedocs.io/en/latest/v1.1.5/1-compression-system/ Compression System Documentation
 */
class Compressor
{
    use \phpSPA\Core\Utils\HtmlCompressor;

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
