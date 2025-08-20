<?php

namespace Component;

/**
 * Converts an associative array of HTML attributes into a string suitable for HTML tags.
 *
 * This utility function facilitates the dynamic creation of HTML attributes from
 * PHP arrays, making it easier to generate flexible and reusable HTML components
 * within the phpSPA framework.
 *
 * @param array $HtmlAttr Associative array where keys are attribute names and values are attribute values.
 * @package phpSPA\Component
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/5-html-attr-in-array-to-string-function
 * @since v1.1.0
 * @return string String of HTML attributes for use in HTML elements.
 */
function HTMLAttrInArrayToString(array $HtmlAttr): string
{
    $attr = '';
    foreach ($HtmlAttr as $AttrName => $AttrValue) {
        $attr .= " $AttrName=\"$AttrValue\"";
    }
    return $attr;
}
