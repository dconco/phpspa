<?php

use phpSPA\Component;
use function phpSPA\Component\createState;
use function phpSPA\Component\useFunction;

function HelloWorld($name)
{
	return ['data' => "Hello $name", 'id' => 3];
}

$caller = useFunction('HelloWorld');

return (new Component(function () {
	$counter = createState('counter', 0);
	$counter($counter() + 1);

	return <<<HTML
	    <button id="btn">
	        Clicks: {$counter}
	    </button>
HTML;
}))
	->route(['/phpspa/template/counter', '/counter'])
	->title('Counter Component')

	->script(
		fn() => <<<JS
		   let btn = document.getElementById('btn')

		   btn.addEventListener('click', async () => {
		      let res = await phpspa.__call('{$caller->token}', 'Dave')
		      alert(res.data)
		   })
		JS
	);
