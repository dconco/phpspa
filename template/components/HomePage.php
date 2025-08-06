<?php

use phpSPA\Component;

use phpSPA\Http\Request;
use function phpSPA\Component\import;
use function phpSPA\Component\createState;

return (new Component(function (Request $request): string {
	$name = $request('name', 'dconco');
	$counter = createState('counter', 0);
	$icon = import(__DIR__ . '/../../docs/img/android-chrome-192x192.png');

	return <<<HTML
		   <style data-type="phpspa/css">
		      body {
		         background-color: #d9cdcd;
		         font-family: Arial, sans-serif;
		      }
		   </style>

		   <div>
		      <img src="{$icon}" />
		      <p>Welcome to my PHP SPA project! @$name</p>
		      <br />
		      <button id="btn" onclick="setState('counter', $counter + 1)">Counter: $counter</button>
		      <PhpSPA.Component.Link to="./login#hashID" id="link-elem">GO TO LOGIN</PhpSPA.Component.Link>
		      <br>
		      <button onclick="phpspa.navigate('/counter')">Counter</button>
		   </div>
	HTML;
}))
	->title('Home Page')
	->route(['/phpspa/template', '/'])

	->script(
		fn() => <<<JS
		   let btn = document.getElementById('btn');
		   btn.onclick = () => alert('btn clicked')

		   alert('Script Mounted');
		   phpspa.__call("d", "ss")
		JS,
	);
