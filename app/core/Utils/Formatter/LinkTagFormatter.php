<?php

namespace PhpSPA\Core\Utils\Formatter;

/**
 * HTML link tag formatting utilities
 *
 * This class is responsible for formatting and generating HTML link tags
 * within the PhpSPA framework. It handles the creation of `<link />` elements
 * for stylesheets, icons, and other external resources with proper formatting.
 *
 * @package PhpSPA\Core\Utils\Formatter
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @since v1.0.0
 */
class LinkTagFormatter
{
    /**
     * Constructor.
     *
     * This constructor is a placeholder for any necessary initialization for
     * the class.
     */
    public function __construct()
    {
    }

    /**
     * Formats a given tag into a specific format.
     *
     * @param mixed $content The tag to be formatted.
     * @return mixed The formatted tag.
     */
    public static function format(&$content): void
    {
        $pattern = '/<Link(S?)\s+([^>]+)\/?\/>/';

        $content = preg_replace_callback(
            $pattern,
            function ($matches) {
                $attributes = $matches[2]; // Extract the attributes: 'path="hello" name="value" id=1 role=["admin", "user"]'

                $labelPattern = '/label=["|\']([^"]+)["|\']/';
                $toPattern = '/to=["|\']([^"]+)["|\']/';

                // Extract the 'label' attribute value using a regular expression
                $attributes = preg_replace_callback(
                    $labelPattern,
                    function ($matches) {
                        global $label;
                        $label = $matches[1];
                        return null;
                    },
                    $attributes,
                );

                // Extract the 'to' attribute value using a regular expression
                $attributes = preg_replace_callback(
                    $toPattern,
                    function ($matches) {
                        global $to;
                        $to = $matches[1];
                        return null;
                    },
                    $attributes,
                );

                global $label;
                global $to;

                return '<a href="' . $to . '" ' . trim($attributes) . ' data-type="phpspa-link-tag">' . $label . '</a>';
            },
            $content,
        );
    }
}
