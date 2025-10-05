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
	
	$Button = fn ($counter) => <<<HTML
		<button id="btn">
			Clicks: {$counter}
		</button>
	HTML;


	$template = <<<HTML
		<div style="text-align: center; margin-top: 2rem;">
			<h2>Counter Component</h2>
			<p>This is a simple counter component demonstrating state management.</p>

			<@Button counter="{$counter}" />

			<script>
				const btn = document.getElementById('btn')

				btn.onclick = async () => {
					const res = await {$caller($counter)}
					setState('counter', $counter + 1)
					alert(res.data)
				}
			</script>
		</div>
	HTML;

	return [
		'template' => $template,
		'scope' => [
			'Button' => @$Button
		],
	];
}))
    ->route(['/phpspa/template/counter', '/counter'])
    ->title('Counter Component');
