<?php

namespace phpSPA\Core\Config;

use phpSPA\Core\Utils\HtmlCompressor;

/**
 * Compression Configuration
 *
 * Manages HTML compression settings for phpSPA applications.
 * Provides easy configuration options for different environments.
 *
 * @package phpSPA\Core\Config
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 */
class CompressionConfig
{
    /**
     * Environment presets
     */
    public const ENV_DEVELOPMENT = 'development';
    public const ENV_STAGING = 'staging';
    public const ENV_PRODUCTION = 'production';

    /**
     * Initialize compression settings based on environment
     *
     * @param string $environment Environment name
     * @return void
     */
    public static function initialize(string $environment = self::ENV_PRODUCTION): void
    {
        switch ($environment) {
            case self::ENV_DEVELOPMENT:
                self::setupDevelopment();
                break;

            case self::ENV_STAGING:
                self::setupStaging();
                break;

            case self::ENV_PRODUCTION:
            default:
                self::setupProduction();
                break;
        }
    }

    /**
     * Setup development environment (minimal compression)
     *
     * @return void
     */
    private static function setupDevelopment(): void
    {
        HtmlCompressor::setLevel(HtmlCompressor::LEVEL_NONE);
        HtmlCompressor::setGzipEnabled(false);
    }

    /**
     * Setup staging environment (basic compression)
     *
     * @return void
     */
    private static function setupStaging(): void
    {
        HtmlCompressor::setLevel(HtmlCompressor::LEVEL_BASIC);
        HtmlCompressor::setGzipEnabled(true);
    }

    /**
     * Setup production environment (aggressive compression)
     *
     * @return void
     */
    private static function setupProduction(): void
    {
        HtmlCompressor::setLevel(HtmlCompressor::LEVEL_AGGRESSIVE);
        HtmlCompressor::setGzipEnabled(true);
    }

    /**
     * Custom compression configuration
     *
     * @param int $level Compression level (0-3)
     * @param bool $gzip Enable gzip compression
     * @return void
     */
    public static function custom(int $level, bool $gzip = true): void
    {
        HtmlCompressor::setLevel($level);
        HtmlCompressor::setGzipEnabled($gzip);
    }

    /**
     * Auto-configure based on server environment
     *
     * @return void
     */
    public static function autoDetect(): void
    {
        // Check for common development indicators
        if (self::isDevelopmentEnvironment()) {
            self::initialize(self::ENV_DEVELOPMENT);
        } elseif (self::isStagingEnvironment()) {
            self::initialize(self::ENV_STAGING);
        } else {
            self::initialize(self::ENV_PRODUCTION);
        }
    }

    /**
     * Check if running in development environment
     *
     * @return bool
     */
    private static function isDevelopmentEnvironment(): bool
    {
        return (
            // Check environment variables
            (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') ||
            (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'development') ||

            // Check for localhost
            (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], [
                'localhost', '127.0.0.1', '::1'
            ])) ||

            // Check for development domains
            (isset($_SERVER['HTTP_HOST']) && preg_match('/\.(local|dev|test)$/', $_SERVER['HTTP_HOST'])) ||

            // Check if running via CLI (development server)
            php_sapi_name() === 'cli-server'
        );
    }

    /**
     * Check if running in staging environment
     *
     * @return bool
     */
    private static function isStagingEnvironment(): bool
    {
        return (
            (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'staging') ||
            (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'staging') ||
            (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'staging') !== false)
        );
    }

    /**
     * Get current compression settings info
     *
     * @return array
     */
    public static function getInfo(): array
    {
        return [
            'environment' => self::detectCurrentEnvironment(),
            'compression_enabled' => true, // Always enabled when class is used
            'gzip_supported' => function_exists('gzencode'),
            'client_accepts_gzip' => isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
                                   strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false,
        ];
    }

    /**
     * Detect current environment
     *
     * @return string
     */
    private static function detectCurrentEnvironment(): string
    {
        if (self::isDevelopmentEnvironment()) {
            return self::ENV_DEVELOPMENT;
        } elseif (self::isStagingEnvironment()) {
            return self::ENV_STAGING;
        } else {
            return self::ENV_PRODUCTION;
        }
    }
}
