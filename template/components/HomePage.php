<?php

use phpSPA\Http\Request;

function HomePage (array $path, Request $request): string
{
   $name = $request('name') ?? 'dconco';

   print_r($path);

   return <<<HTML
      <div>
         <p>Welcome to my PHP SPA project! @$name</p>
         <Link to="/login" label="GO TO LOGIN" />
      </div>
   HTML;
}