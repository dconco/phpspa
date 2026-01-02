<?php

namespace PhpSPA\Core\Helper;

/**
 * Component attribute parsing utilities
 *
 * This trait provides methods for parsing HTML attributes from string format
 * into array format, facilitating component attribute handling and manipulation
 * within the PhpSPA framework.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @var string|array $attributes
 */
trait ComponentParser
{
    private static function parseAttributesToArray($attributes): array
    {
        $attrArray = [];
        $pattern = '/([a-zA-Z_-]+)\s*=\s*"([^"]*)"/';

        // Remove newlines and excessive spaces for easier parsing
        $normalized = preg_replace('/\s+/', ' ', trim($attributes));

        if (preg_match_all($pattern, $normalized, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $decoded = base64_decode($match[2], true);

                if ($decoded !== false) {
                    $unserialized = @unserialize($decoded);

                    if ($unserialized !== false || $decoded === 'b:0;') {
                        $attrArray[$match[1]] = $unserialized;
                        continue;
                    }
                }
                $attrArray[$match[1]] = $match[2];
            }
        }

        return $attrArray;
    }
}
