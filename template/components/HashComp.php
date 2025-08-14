<?php

use function Component\HTMLAttrInArrayToString;

function HashComp($children, ...$attr)
{
	$attr = HTMLAttrInArrayToString($attr);

	return <<<HTML
	   <div $attr>
	      <p>{$children}</p>
	   </div>
	HTML;
}
