<?php

namespace Component;

use PhpSPA\Core\Helper\FileHandler;
use PhpSPA\Core\Utils\ImportedFile;
use PhpSPA\Exceptions\AppException;

/**
 * Imports file as base64 data URI with MIME type detection.
 *
 * @author dconco <concodave@gmail.com>
 * @param string $file File path to import
 * @return ImportedFile Data URI with MIME type and base64 content
 * @throws AppException If file is invalid or unreadable
 * @see https://phpspa.vercel.app/references/file-import-utility
 */
function import(string $file): ImportedFile
{
    if (!is_file($file)) {
        throw new AppException("Unable to get file: $file");
    }
    if (filesize($file) > 1048576) {
        // 1MB
        throw new AppException("File too large to import: $file");
    }

    $file_type = FileHandler::file_type($file);
    $contents = base64_encode(file_get_contents($file));

    $data = "data:$file_type;base64,$contents";
    return new ImportedFile($data, $file);
}
