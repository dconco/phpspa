<?php

namespace phpSPA\Component;

use phpSPA\Core\Helper\FileHandler;
use phpSPA\Core\Utils\ImportedFile;
use phpSPA\Exceptions\AppException;

/**
 * Embeds file contents as base64 data URI
 * 
 * @param string $file Path to file to import
 * @return string Data URI (format: data:<mime-type>;base64,<content>)
 * @throws AppException If file doesn't exist or can't be read
 */
function import (string $file): ImportedFile
{
   if (!is_file($file))
   {
      throw new AppException("Unable to get file: $file");
   }
   if (filesize($file) > 1048576)
   { // 1MB
      throw new AppException("File too large to import: $file");
   }

   $file_type = FileHandler::file_type($file);
   $contents = base64_encode(file_get_contents($file));

   $data = "data:$file_type;base64,$contents";
   return new ImportedFile($data, $file);
}