<?php

namespace PhpSPA\Core\Utils\Formatter;

/**
 * Bracket interpolation formatting utilities
 *
 * This abstract class provides methods for processing and formatting bracket
 * interpolation syntax ({{ ... }}) within template content. It handles the
 * transformation of template expressions into executable PHP code.
 *
 * @package PhpSPA\Core\Utils\Formatter
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @abstract
 */
abstract class FormatBracketInterpolation
{
    protected function format(&$content): void
    {
        // Replace bracket interpolation {{! ... !}}
        $content = preg_replace(
            '/\{\{!\s*.*?\s*!\}\}/s',
            '',
            $content,
        );

        // Replace bracket interpolation {{ ... }}
        $content = preg_replace_callback(
            '/\{\{\s*(.*?)\s*\}\}/s',
            function ($matches) {
                $val = trim($matches[1], ';');
                return '<' . '?php print_r(' . $val . '); ?' . '>';
            },
            $content,
        );
    }
}
