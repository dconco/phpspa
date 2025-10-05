<?php

use phpSPA\Component;

use function Component\createState;
use function Component\useFunction;

function HelloWorld($name)
{
	return ['data' => "Hello $name", 'id' => 3];
}


function LinkComponent()
{
	$Link = fn () => <<<HTML
		<Component.Link to="/counter">Click me</Component.Link>
	HTML;

	scope(compact('Link'));

	return "<@Link />";
}

return (new Component(function (): string {
	$caller = useFunction('HelloWorld');
	$counter = createState('counter', 0);
	
	// 1. Define all your private components
	$Button = fn ($counter) => <<<HTML
		<button id="btn">
			Clicks: {$counter}
		</button>
	HTML;

	// 2. Register them all in one go using compact()
	scope(compact('Button'));

	return <<<HTML
		<div style="text-align: center; margin-top: 2rem;">
			<h2>Counter Component</h2>
			<p>This is a simple counter component demonstrating state management.</p>

			<@Button counter="{$counter}" />
			<LinkComponent />

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
}))
    ->route(['/counter', '/template/counter'])
    ->title('Counter Component');
