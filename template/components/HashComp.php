<?php

use function Component\HTMLAttrInArrayToString;

class HashCom {
	public function __construct(
		public readonly string $title,
		public readonly string $children
	) {}
}

function Paragraph(array $children, array $attr)
{
	return <<<HTML
	   <p attr="{$attr['style']}">{$children['children']}</p>
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
	         <Paragraph attr="{$sss}">{$children()}</Paragraph>
	      </Component.Link>
	   </div>
	HTML;
}
