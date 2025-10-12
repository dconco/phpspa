<?php

namespace Component;

/**
 * Converts HTML attributes array to string.
 *
 * @author dconco <concodave@gmail.com>
 * @param array $HtmlAttr Array of attribute name-value pairs.
 * @return string HTML attributes string.
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/6-html-attr-in-array-to-string-function HTML Attributes Documentation
 */
function HTMLAttrInArrayToString(array $HtmlAttr): string
{
    $attr = '';
    foreach ($HtmlAttr as $AttrName => $AttrValue) {
        $attr .= " $AttrName=\"$AttrValue\"";
    }
    return $attr;
}
