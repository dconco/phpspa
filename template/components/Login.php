<?php

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
         <form action="/login" method="POST">
            <label>Enter your Username:</label>
            <input type="text" />
            <br />
            <label>Enter your Password:</label>
            <input type="password" />
            <br />
            <button>LOGIN</button>
         </form>
      </div>
   HTML;
}