<?php

namespace PhpSPA\Core\Helper;

use PhpSPA\Exceptions\AppException;

/**
 * File handling utilities
 *
 * This class provides methods for file operations, including MIME type detection,
 * file validation, and other file-related utilities within the PhpSPA framework.
 * It ensures secure and reliable file handling across the application.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class FileHandler 
{
    /**
     * Shared helper to verify the environment and return a configured finfo instance.
     * 
     * @throws AppException
     */
    private static function getFinfoInstance(int $flags): \finfo
    {
        if (!extension_loaded('fileinfo')) {
            throw new AppException('Fileinfo extension is not enabled. Please enable it in your php.ini configuration.');
        }

        return new \finfo($flags);
    }

    /**
     * Get the character encoding for a file.
     */
    public static function fileCharset(string $filename): false|string
    {
        if (!is_file($filename)) return false;

        $finfo = self::getFinfoInstance(FILEINFO_MIME_ENCODING);
        return $finfo->file($filename);
    }

    /**
     * Get the MIME content type for a file.
     */
    public static function fileType(string $filename): false|string
    {
        if (!is_file($filename)) return false;

        $finfo = self::getFinfoInstance(FILEINFO_MIME_TYPE);
        return $finfo->file($filename);
    }

    /**
     * Retrieve both MIME type and charset securely in a single file-read operation.
     * 
     * @return array{type: string, charset: string}|false
     */
    public static function fileMimeMeta(string $filename): array|false
    {
        if (!is_file($filename)) return false;

        // Use FILEINFO_MIME to get both text representations combined
        $finfo = self::getFinfoInstance(FILEINFO_MIME);
        $raw_mime = $finfo->file($filename);

        if (!$raw_mime) return false;

        // Separate the type and charset safely
        $parts = explode('; charset=', $raw_mime);

        return [
            'type' => $parts[0] ?? 'application/octet-stream',
            'charset' => $parts[1] ?? 'binary'
        ];
    }
}
