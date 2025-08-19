<?php

namespace Component;

/**
 * Converts an associative array of HTML attributes into a string suitable for HTML tags.
 *
 * @param array $HtmlAttr Associative array where keys are attribute names and values are attribute values.
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/5-html-attr-in-array-to-string-function
 * @package phpSPA\Component
 * @since 1.1.0
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
