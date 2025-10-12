<?php

use PhpSPA\Component;

use function Component\useState;
use function Component\useEffect;
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
	$counter = useState('counter', 0);
	$newMsg = '';

	useEffect(function () use ($counter, &$newMsg) {
		$newCounter = $counter() + 1;
		$name = ['Dave', 'John', 'Jane'][array_rand(['Dave', 'John', 'Jane'])];
		$newMsg = "Counter updated to: $counter but changed to $newCounter by $name";

		$counter($newCounter);
	}, [[], $counter]);

	// 1. Define all your private components
	$Button = fn ($counter) => <<<HTML
		<button id="btn">
			Clicks: {$counter}
		</button>
		<br />
		<span style="color: green;">{$newMsg}</span>
		HTML;
		
		// 2. Register them all in one go using compact()
		scope(compact('Button'));
		
		return <<<HTML
		<div style="text-align: center; margin-top: 2rem;">
			<h2>Counter Component</h2>
			<p>This is a simple counter component demonstrating state management.</p>
			
			<@Button counter="{$counter}" />
			<br />
			<LinkComponent />

			<script>
				const btn = document.getElementById('btn')

				btn.onclick = async () => {
					const res = await {$caller($counter)}
					setState('counter', $counter + 1)
					// alert(res.data)
				}
			</script>
		</div>
	HTML;
}))
    ->route(['/counter', '/template/counter'])
    ->title('Counter Component');
