<?php

use PhpSPA\Component;

use function Component\createState;

include_once 'HashComp.php';

function Login(): string
{
    $loginDetails = createState('login', [
        'username' => null,
        'password' => null,
    ]);
    $loading = createState('loading', false);

    $loadingText = "$loading" == 'true' ? 'Loading' : 'LOGIN';
    $buttonDisabled = "$loading" == 'true' ? 'disabled' : '';

    $buttonHtml = "<button id=\"btn\" $buttonDisabled>$loadingText</button>";

    $username = $loginDetails()['username'];
    $password = $loginDetails()['password'];

    if (!empty($username) && !empty($password)) {
        sleep(2);
        if ($username !== 'admin' || $password !== 'admin') {
            http_response_code(401);
            return "Incorrect Login Details: <br>Username: $username<br>Password: $password";
        }

        return <<<HTML
		   Login Successful:<br>Username: $username<br>Password: $password
		   <Component.Navigate path="dashboard" />
		HTML;
    }

    return <<<HTML
	   <div>
	      <form action="/phpspa/template/login" method="POST">
	         <label>Enter your Username:</label>
	         <input type="text" id="username" value="{$username}" />
	         <br />
	         <label>Enter your Password:</label>
	         <input type="password" id="password" value="{$password}" />
	         <br />
	         {$buttonHtml}
	      </form>
	      
	      <HashComp id="hashID" class="hash" children="This is an Hashed element" />
	   </div>
	HTML;
}

return (new Component('Login'))
    ->method('POST|GET')
    ->title('Login Page')
    ->route('/login')
    ->caseInsensitive()

    ->styleSheet(
        fn () => <<<CSS
            #hashID {
                padding-top: 100vh;
                padding-bottom: 100vh;
            }
            body {
                background-color: #c0c0c0;
                font-family: Arial, sans-serif;
            }
        CSS,
        'login-style'
    )

    ->script(
        fn () => <<<JS
		const submitBtn = document.getElementById('btn')

		submitBtn.addEventListener('click', (e) => {
			e.preventDefault();
			const username = document.getElementById('username').value
			const password = document.getElementById('password').value

			if (username.trim() !== '' && password.trim() !== '') {
				setState('loading', "true")
					.then(() => setState('login', { username, password }))
					.then(() => setState('loading', "false"))
			}
		})
		JS,
        'login-default-script'
    );
