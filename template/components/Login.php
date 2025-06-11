<?php

use phpSPA\Component;
use phpSPA\Http\Request;

function Login (Request $request): string
{
   if ($_SERVER['REQUEST_METHOD'] == "POST")
   {
      $username = $request("username");
      $password = $request("password");

      if ($username !== 'admin' && $password !== 'admin')
      {
         http_response_code(401);
         return json_encode([ 'message' => 'Incorrect Login Details' ]);
      }

      return json_encode([ 'message' => 'Login Successful' ]);
   }

   return <<<HTML
      <div>
         <form action=/phpspa/template/logina method=POST>
            <label>Enter your Username:</label>
            <input type=text />
            <br />
            <label>Enter your Password:</label>
            <input type=password />
            <br />
            <button id=btn>LOGIN</button>
         </form>
         <div id=hashID>
            <p>Hello</p>
         </div>
      </div>
   HTML;
}


return (new Component('Login'))
   ->method('GET|POST')
   ->title('Login Page')
   ->route('/phpspa/template/login')
   ->caseInsensitive()

   ->script(function ()
   {
      return <<<JS
         console.log('Script Mounted');
      JS;
   })

   ->script(function ()
   {
      return <<<JS
         document.getElementById("btn").onclick = (ev) => {
            ev.preventDefault();
            phpspa.navigate("/phpspa/template/logina");
         };
      JS;
   });