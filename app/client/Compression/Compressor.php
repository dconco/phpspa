<?php

namespace PhpSPA\Compression;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for PhpSPA
 * to reduce payload sizes and improve performance. This class implements
 * various compression levels and environment-specific optimizations.
 *
 * @package PhpSPA\Compression
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @see https://phpspa.vercel.app/v1.1.5/1-compression-system/ Compression System Documentation
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
