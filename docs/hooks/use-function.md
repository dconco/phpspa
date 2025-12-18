# Calling PHP from JavaScript (`useFunction`)

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

Often, you need to run PHP logic — like saving to a database — without a full page reload. The `useFunction` hook makes this simple without creating separate API routes.

!!! info "Secure Bridge"
    It securely exposes a PHP function so your client-side JavaScript can call it directly.

---

!!! note "Required Namespace"
    ```php
    <?php
    use function Component\useFunction;
    ```
    Include this at the top of your PHP files to use the `useFunction` hook.

---

## Syntax

```php
<?php

function phpFunction($arg) {
    return "result";
};

$caller = useFunction("phpFunction");
```

**In JavaScript:**

```javascript
const result = await {$caller("'arg value'")};
```

The `useFunction` hook wraps a PHP function and returns a caller that can be invoked from JavaScript.

---

## Example: A Simple Greeter Form

Let's build a form where a user enters their name, and the server sends back a personalized greeting.

**1. Define the PHP Function**

```php
<?php

function sayHello(string $name): string {
    return "Hello, $name!";
};
```

This function accepts a name and returns a greeting.

**2. Wrap with useFunction**

```php
<?php

$greeter = useFunction("sayHello");
```

This creates a JavaScript-callable version of your PHP function.

**3. Create the HTML Form**

```php
<?php

echo <<<HTML
    <input type="text" id="nameInput" placeholder="Enter your name">
    <button id="greetBtn">Greet Me</button>
HTML;
```

Simple input and button for user interaction.

**4. Call from JavaScript**

```javascript
const nameInput = document.getElementById('nameInput');
const greetBtn = document.getElementById('greetBtn');

greetBtn.onclick = async () => {
    const name = nameInput.value;
    const greeting = await {$greeter('name')}; // Take note: the value in the parameter is js code
    alert(greeting);
};
```

The JavaScript calls the PHP function and displays the result.

---

### Complete Example

```php
<?php

use PhpSPA\Component;
use function Component\useFunction;

$greeterPage = new Component(function () {
    // 1. Define the PHP function you want to call.
    function sayHello(string $name, int $age): string {
        return [
            "name" => $name,
            "age" => $age,
            "greeting" => "Hello, $name! You are $age years old",
        ];
    };

    // 2. Wrap your function with useFunction().
    $greeter = useFunction("sayHello");

    echo <<<HTML
        <input type="text" id="nameInput" placeholder="Enter your name">
        <input type="number" id="ageInput" placeholder="Enter your age">
        <button id="greetBtn">Greet Me</button>

        <script>
            const nameInput = document.getElementById('nameInput');
            const ageInput = document.getElementById('ageInput');
            const greetBtn = document.getElementById('greetBtn');

            greetBtn.onclick = async () => {
                const name = nameInput.value;
                const age = Number(ageInput.value);

                // 3. Call the PHP function from JavaScript!
                const greeting = await {$greeter('name', 'age')};

                // The returned value is the direct output of your PHP function.
                console.log(greeting); // Shows a javascript object like:

                // {
                //    "name": "Dave",
                //    "age": 17,
                //    "greeting": "Hello, Dave!"
                // }

                alert(greeting.name); // Shows "Dave".
            };
        </script>
    HTML;
});

$greeterPage->route('/greeter');
```

!!! tip "How It Works"
    When you echo the `$greeter('name')` object, it generates a JavaScript snippet like `phpspa.__call('some-secure-token', name)`. The arguments you pass to the caller in PHP are inserted directly as JavaScript expressions.

!!! success "Return Values"
    The `phpspa.js` library handles the secure AJAX request and returns the exact data from your PHP function (string, array, object, etc.) as a JavaScript promise.
