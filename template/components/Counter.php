<?php

use phpSPA\Component;
use function Component\createState;
use function Component\useFunction;

function HelloWorld($name)
{
	return ['data' => "Hello $name", 'id' => 3];
}

//$caller = useFunction('HelloWorld');

return (new Component(function () {
	$counter = createState('counter', 0);

	return <<<HTML
	      <button id="btn">
	         Clicks: {$counter}
	      </button>

	      <script>
	         const btn = document.getElementById('btn')

	         btn.onclick = () => {
	            const res = setState('counter', $counter+1)
	         }
	      </script>
	HTML;
}))
	->route(['/phpspa/template/counter', '/counter'])
	->title('Counter Component');
