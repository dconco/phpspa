<?php

use PhpSPA\Component;
use PhpSPA\Http\Request;
use PhpSPA\Http\Security\Nonce;

use function Component\import;
use function Component\createState;

return (new Component(function (Request $request): string {
   $name = $request('name', 'dconco');
	$nonce = Nonce::attr();
   $counter = createState('counter', 0);
   $icon = import(__DIR__ . '/../../docs/img/android-chrome-192x192.png');

   return <<<HTML
		<div>
			<img src="" />
			<p>Welcome to my PHP SPA project! @$name</p>
			<br />
			<button id="btn">Counter: $counter</button>
			<Component.Link to="./login#hashID" id="link-elem">GO TO LOGIN</Component.Link>
			<br />
			<button id="navigate-btn">Counter</button>
		</div>

		<script $nonce>
			document.getElementById('btn').onclick = function() {
				setState('counter', $counter + 1);
			};
			document.getElementById('navigate-btn').onclick = function() {
				phpspa.navigate('./counter');
			};
		</script>
	HTML;
}))
   ->title('Home Page')
   ->route(['/', '/template'])

   ->styleSheet(
      fn () => <<<CSS
			body {
				background-color: #d9cdcd;
				font-family: Arial, sans-serif;
			}
		CSS,
		'homepage-style'
   )

   ->script(
      fn () => <<<JS
		  	// Component script - should execute AFTER global script
		  	console.log('4. HomePage component script executing');
		  	
		  	// Test if global utilities are available
		  	if (window.globalUtils) {
		  		window.globalUtils.executionOrder.push('HomePage component script executed');
		  		window.globalUtils.log('HomePage component can access global utilities!');
		  	} else {
		  		console.error('Global utilities not available - execution order problem!');
		  	}

			const observerOptions = {
				threshold: 0.1
			}

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
					e.preventDefault() // prevent form submission
					alert('Thank you for your message! We will get back to you soon.')
				})
			}
		JS,
		'homepage-script'
   );
