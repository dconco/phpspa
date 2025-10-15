<?php

use function Component\HTMLAttrInArrayToString;

function Paragraph($children)
{
	return <<<HTML
	   <p>{$children}</p>
	HTML;
}

function HashComp($children, ...$attr)
{
	$attr = HTMLAttrInArrayToString($attr);

	return <<<HTML
	   <div $attr>
	      <Component.Link>
	         <Paragraph>{$children}</Paragraph>
	      </Component.Link>
	   </div>
	HTML;
}
