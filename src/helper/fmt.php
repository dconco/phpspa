<?php

use PhpSPA\Core\Utils\Formatter\FormatComponent;

/**
 * Formats data for use in component props.
 * 
 * This function prepares and formats various data types to be safely passed
 * as properties to components, ensuring proper serialization and type handling.
 *
 * @param mixed $data The data to be formatted. Can be of any type (string, array, object, etc.)
 */
function fmt(&...$data): void {
   foreach ($data as &$value) {
      $value = new FormatComponent($value);
   }
}
