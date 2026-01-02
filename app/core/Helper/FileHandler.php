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
     * Get the MIME content type for a file.
     *
     * This method returns the MIME type of a file based on its extension
     * and the file's contents. If the `fileinfo` extension is not enabled,
     * an exception is thrown.
     *
     * @param string $filename The path to the file whose MIME type is being determined.
     * @return bool|string The MIME type of the file as a string, or `false` if the file doesn't exist.
     * @throws AppException If the `fileinfo` extension is not enabled in PHP.
     */
    public static function fileType(string $filename): bool|string
    {
        if (is_file($filename)) {
            if (!extension_loaded('fileinfo')) {
                throw new AppException(
                    'Fileinfo extension is not enabled. Please enable it in your php.ini configuration.',
                );
            }

            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($file_info, $filename);
            finfo_close($file_info);

            $file_ext = explode('.', $filename);
            $file_ext = strtolower(end($file_ext));

            if (
                $file_type === 'text/plain' ||
                $file_type === 'application/x-empty' ||
                $file_type === 'application/octet-stream'
            ) {
                return match ($file_ext) {
                    'css' => 'text/css',
                    'txt' => 'text/plain',
                    'csv' => 'text/csv',
                    'htm' => 'text/htm',
                    'html' => 'text/html',
                    'php' => 'text/x-php',
                    'xml' => 'text/xml',
                    'js' => 'application/javascript',
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'json' => 'application/json',
                    'md' => 'text/markdown',
                    'ppt' => 'application/mspowerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'swf' => 'application/x-shockwave-flash',
                    'ai' => 'application/postscript',
                    'odt' => 'application/vnd.oasis.opendocument.text',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    default => 'text/plain',
                };
            } else {
                return $file_type;
            }
        } else {
            return false;
        }
    }
}
