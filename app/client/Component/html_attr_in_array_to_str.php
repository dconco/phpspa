<?php

namespace Component;

use PhpSPA\Core\Utils\Validate;

/**
 * Converts HTML attributes array to string.
 *
 * @package Component
 * @author dconco <me@dconco.tech>
 * @param array $HtmlAttr Array of attribute name-value pairs.
 * @return string HTML attributes string.
 * @see https://phpspa.tech/components HTML Attributes Documentation
 */
function HTMLAttrInArrayToString(array $HtmlAttr): string
{
    $attr = [];

    foreach ($HtmlAttr as $AttrName => $AttrValue) {
        if ((!\is_string($AttrName) || !\is_string($AttrValue)) && $AttrValue !== true) continue;

        [$AttrName, $AttrValue] = Validate::validate([$AttrName, $AttrValue]);

        $attr[] = empty($AttrValue) || $AttrValue === true ? $AttrName : "$AttrName=\"$AttrValue\"";
    }
    return implode(' ', $attr);
}
