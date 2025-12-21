<?php

namespace Component;

/**
 * Renders SPA navigation link component.
 *
 * @package Component
 * @author dconco <me@dconco.tech>
 * @param string $children Link content
 * @param string $to Target URL/route
 * @param string ...$HtmlAttr Additional HTML attributes
 * @return string HTML anchor element
 * @see https://phpspa.tech/navigations/link-component
 */
function Link(string $children, string $to = '#', string ...$HtmlAttr): string
{
    $attr = HTMLAttrInArrayToString($HtmlAttr);

    return <<<HTML
	   <a href="{$to}" data-type="phpspa-link-tag" $attr>{$children}</a>
	HTML;
}
