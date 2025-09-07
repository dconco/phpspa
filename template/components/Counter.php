<?php

use phpSPA\Component;

use function Component\createState;
use function Component\useFunction;

function HelloWorld($name)
{
    return ['data' => "Hello $name", 'id' => 3];
}

return (new Component(function () {
    $caller = useFunction('HelloWorld');
    $counter = createState('counter', 0);

    return <<<HTML
	      <button id="btn">
	         Clicks: {$counter}
	      </button>

	      <script>
	         const btn = document.getElementById('btn')

	         btn.onclick = async () => {
	            const res = await {$caller($counter)}
					setState('counter', $counter + 1)
	            alert(res.data)
	         }
	      </script>
	HTML;
}))
    ->route(['/phpspa/template/counter', '/counter'])
    ->title('Counter Component');
