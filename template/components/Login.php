<?php

use PhpSlides\Core\Http\Request;

function Login (Request &$req): string
{
   if ($req->method() == "POST")
   {
      $username = $req->post("username");
      $password = $req->post("password");

      if ($username !== 'admin' && $password !== 'admin')
      {
         http_response_code(401);
         return json_encode([ 'message' => 'Incorrect Login Details' ]);
      }

      return json_encode([ 'message' => 'Login Successful' ]);
   }

   return <<<HTML
      <div>
         <form action="{$req->uri()}" method="POST">
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