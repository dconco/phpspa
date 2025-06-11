<?php

use phpSPA\Component;
use function phpSPA\Component\createState;

include_once 'HashComp.php';
include_once realpath(__DIR__ . '/../../app/core/Component/createState.php');

function Login (): string
{
   $hashComp = HashComp(children: "This is an Hashed element");

   $loginDetails = createState('login', [
      'username' => null,
      'password' => null
   ]);
   $loading = createState('loading', false);

   $username = $loginDetails()['username'];
   $password = $loginDetails()['password'];

   if (!empty($username) && !empty($password))
   {
      sleep(2);
      if ($username !== 'admin' && $password !== 'admin')
      {
         http_response_code(401);
         return 'Incorrect Login Details';
      }
      return 'Login Successful';
   }

   return <<<HTML
      <style data-type="phpspa/css">
         #hashID {
            padding-top: 100vh;
            padding-bottom: 100vh;
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
            <button id="btn">{$loading ? 'Loading' : 'LOGIN'}</button>
         </form>
         <!-- <Hash children="This is an Hashed element" /> -->
         $hashComp;
      </div>

      <script data-type="phpspa-script">
         const submitBtn = document.getElementById('btn');
         
         submitBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (username.trim() !== '' && password.trim() !== '') {
               console.log("Submitting...")
               phpspa.setState('login', { username, password })
                  .then(() => console.log("Submitted"))
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