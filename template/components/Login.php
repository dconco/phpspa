<?php

use phpSPA\Component;
use function phpSPA\Component\createState;

include_once 'HashComp.php';

function Login (): string
{
   $loginDetails = createState('login', [
      'username' => null,
      'password' => null
   ]);
   $loading = createState('loading', false);

   $loadingText = "$loading" == "true" ? 'Loading' : 'LOGIN';
   $buttonDisabled = "$loading" == "true" ? 'disabled' : '';

   $buttonHtml = "<button id=\"btn\" $buttonDisabled>$loadingText</button>";

   $username = "$loginDetails"['username'];
   $password = $loginDetails()['password'];

   if (!empty($username) && !empty($password))
   {
      sleep(2);
      if ($username !== 'admin' && $password !== 'admin')
      {
         http_response_code(401);
         return "Incorrect Login Details: <br>Username: $username<br>Password: $password";
      }
      return "Login Successful:<br>Username: $username<br>Password: $password";
   }

   return <<<HTML
      <style data-type="phpspa/css">
         #hashID {
            padding-top: 100vh;
            padding-bottom: 100vh;
         }
         body {
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
         }
      </style>

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
         <HashComp id="hashID" class="hash">
            This is an Hashed element
         </HashComp>
      </div>

      <script data-type="phpspa/script">
         const submitBtn = document.getElementById('btn');
         
         submitBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (username.trim() !== '' && password.trim() !== '') {
               phpspa.setState('loading', "true")
                  .then(() => phpspa.setState('login', { username, password }))
                  .then(() => phpspa.setState('loading', "false"));
            }
         })
      </script>
   HTML;
}

return (new Component('Login'))
   ->method('POST|GET')
   ->title('Login Page')
   ->route('/phpspa/template/login')
   ->caseInsensitive()

   ->script(fn () => <<<JS
         console.log('Script Mounted');
      JS);