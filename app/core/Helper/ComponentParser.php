<?php

namespace phpSPA\Core\Helper;

/**
 * Component attribute parsing utilities
 *
 * This trait provides methods for parsing HTML attributes from string format
 * into array format, facilitating component attribute handling and manipulation
 * within the phpSPA framework.
 *
 * @package phpSPA\Core\Helper
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @var string|array $attributes
 */
trait ComponentParser
{
    private static function parseAttributesToArray($attributes): array
    {
        $attrArray = [];
        $pattern = '/([a-zA-Z_-]+)\s*=\s*(?|"([^"]*)"|\'([^\']*)\')/';

        // Remove newlines and excessive spaces for easier parsing
        $normalized = preg_replace('/\s+/', ' ', trim($attributes));

        if (preg_match_all($pattern, $normalized, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrArray[$match[1]] = $match[2];
            }
        }

        return $attrArray;
    }
}
