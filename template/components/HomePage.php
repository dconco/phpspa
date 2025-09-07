<?php

use phpSPA\Component;
use phpSPA\Http\Request;

use function Component\import;
use function Component\createState;

return (new Component(function (Request $request): string {
    $name = $request('name', 'dconco');
    $counter = createState('counter', 0);
    $icon = import(__DIR__ . '/../../docs/img/android-chrome-192x192.png');

    return <<<HTML
		<style>
			body {
				background-color: #d9cdcd;
				font-family: Arial, sans-serif;
			}
		</style>

		<div>
			<img src="" />
			<p>Welcome to my PHP SPA project! @$name</p>
			<br />
			<button id="btn" onclick="setState('counter', $counter + 1)">Counter: $counter</button>
			<Component.Link to="./login#hashID" id="link-elem">GO TO LOGIN</Component.Link>
			<br />
			<button onclick="phpspa.navigate('/counter')">Counter</button>
		</div>
	   
	   <script>
		   alert('Script Mounted')

			const observer = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('fade-in')
					}
			})
			}, observerOptions)

			const form = document.querySelector('form')
			if (form) {
				form.addEventListener('submit', function(e) {
						e.preventDefault() /* prevent form submission */
						alert('Thank you for your message! We will get back to you soon.')
				})
			}
		</script>
	HTML;
}))
    ->title('Home Page')
    ->route(['/phpspa/template', '/'])

    ->script(
        fn () => <<<JS
		JS,
    );
