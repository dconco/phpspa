<?php

namespace Component;

/**
 * Renders SPA navigation link component.
 *
 * @author dconco <concodave@gmail.com>
 * @param string $children Link content
 * @param string $to Target URL/route
 * @param string ...$HtmlAttr Additional HTML attributes
 * @return string HTML anchor element
 * @see https://phpspa.vercel.app/navigations/link-component
 */
function Link(string $children, string $to = '#', string ...$HtmlAttr): string
{
    $attr = HTMLAttrInArrayToString($HtmlAttr);

    return <<<HTML
	   <a href="{$to}" data-type="phpspa-link-tag" $attr>{$children}</a>
	HTML;
}
