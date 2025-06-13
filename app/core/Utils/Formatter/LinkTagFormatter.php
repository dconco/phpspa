<?php

namespace phpSPA\Core\Utils\Formatter;

/**
 * Class LinkTagFormatter
 *
 * Responsible for formatting and generating HTML link tags.
 * Typically used to create `<Link />` elements for stylesheets, icons, and other resources.
 *
 * @package phpSPA\Utils\Formatter
 */
class LinkTagFormatter
{
	/**
	 * Constructor.
	 *
	 * This constructor is a placeholder for any necessary initialization for
	 * the class.
	 */
	public function __construct ()
	{
	}

	/**
	 * Formats a given tag into a specific format.
	 *
	 * @param mixed $content The tag to be formatted.
	 * @return mixed The formatted tag.
	 */
	static public function format (&$content): void
	{
		$pattern = '/<Link(S?)\s+([^>]+)\/?\/>/';

		$content = preg_replace_callback(
		 $pattern,
		 function ($matches)
		 {
			 $attributes = $matches[2]; // Extract the attributes: 'path="hello" name="value" id=1 role=["admin", "user"]'
 
			 $labelPattern = '/label=["|\']([^"]+)["|\']/';
			 $toPattern = '/to=["|\']([^"]+)["|\']/';

			 // Extract the 'label' attribute value using a regular expression
 			$attributes = preg_replace_callback(
			  $labelPattern,
			  function ($matches)
			 {
				 global $label;
				 $label = $matches[1];
				 return null;
			 },
			  $attributes,
			 );

			 // Extract the 'to' attribute value using a regular expression
 			$attributes = preg_replace_callback(
			  $toPattern,
			  function ($matches)
			 {
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