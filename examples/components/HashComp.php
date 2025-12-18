<?php

use function Component\HTMLAttrInArrayToString;

class HashCom {
	public function __construct(
		public readonly string $title,
		public readonly string $children
	) {}
}

function Paragraph(array $children, array $style)
{
	return <<<HTML
	   <p style="{$style['style']}">{$children['children']}</p>
	HTML;
}

function HashComp($children, ...$attr)
{
	$attr = HTMLAttrInArrayToString($attr);

	$children = function() use ($children) {
		return ['children' => $children];
	};

	$sss = ['style' => 'color: blue; font-weight: bold; font-size: 1.2rem;'];

	fmt($children, $sss);

	return <<<HTML
	   <div $attr>
	      <Component.Link>
	         <Paragraph style="{$sss}">{$children()}</Paragraph>
	      </Component.Link>
	   </div>
	HTML;
}
