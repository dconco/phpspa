<?php

namespace Component;

/**
 * Renders a link component for client-side navigation.
 *
 * @param string $to The destination URL or route.
 * @param string $children The inner HTML or text content of the link.
 * @param string ...$HtmlAttr Additional HTML attributes for the anchor tag.
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @see https://phpspa.readthedocs.io/en/latest/v1.1/4-link-component
 * @since v1.1.0
 * @package phpSPA\Component
 * @return string The rendered HTML anchor element as a string.
 */
function Link(string $children, string $to = '#', string ...$HtmlAttr): string
{
	$attr = HTMLAttrInArrayToString($HtmlAttr);

	return <<<HTML
	   <a href="{$to}" data-type="phpspa-link-tag"$attr>{$children}</a>
	HTML;
}
