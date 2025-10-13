## Calling PHP from JavaScript (`useFunction`)

Often, you need to run PHP logic—like saving to a database—without a full page reload. The `useFunction` hook makes this simple without creating separate API routes.

It securely exposes a PHP function so your client-side JavaScript can call it directly.

### Example: A Simple Greeter Form

Let's build a form where a user enters their name, and the server sends back a personalized greeting.

```php
<?php

use phpSPA\Component;
use function Component\useFunction;

$greeterPage = new Component(function () {
    // 1. Define the PHP function you want to call.
    // It can accept arguments and should return the raw data.
    $sayHello = function (string $name): string {
        return "Hello, " . htmlspecialchars($name) . "!";
    };

    // 2. Wrap your function with useFunction().
    $greeter = useFunction($sayHello);

    echo <<<HTML
        <input type="text" id="nameInput" placeholder="Enter your name">
        <button id="greetBtn">Greet Me</button>

        <script>
            const nameInput = document.getElementById('nameInput');
            const greetBtn = document.getElementById('greetBtn');

            greetBtn.onclick = async () => {
                const name = nameInput.value;

                // 3. Call the PHP function from JavaScript!
                // The argument 'name' here is treated as a JavaScript expression.
                // It becomes the 'name' variable from the line above.
                const greeting = await {$greeter('name')};

                // The returned value is the direct output of your PHP function.
                alert(greeting); // Shows "Hello, [Name]!"
            };
        </script>
    HTML;
});

$greeterPage->route('/greeter');
```

### How It Works

When you echo the `$greeter('name')` object, it generates a JavaScript snippet like `phpspa.__call('some-secure-token', name)`. The arguments you pass to the caller in PHP are inserted directly as JavaScript expressions.

The `phpspa.js` library handles the secure AJAX request and returns the exact data from your PHP function (string, array, object, etc.) as a JavaScript promise.
