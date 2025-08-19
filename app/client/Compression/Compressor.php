<?php

namespace phpSPA\Compression;

/**
 * HTML Compression Utility
 *
 * Provides HTML minification and compression capabilities for phpSPA
 * to reduce payload sizes and improve performance.
 *
 * @package phpSPA\Core\Utils
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 */
class Compressor extends \phpSPA\Core\Utils\HtmlCompressor
{
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
