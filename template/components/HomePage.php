<?php

use phpSPA\Component;
use phpSPA\Http\Request;

return (new Component(function (Request $request): string
{
   $name = $request('name', 'dconco');

   return <<<HTML
      <div>
         <p>Welcome to my PHP SPA project! @$name</p>
         <Link to="./login#hashID" label="GO TO LOGIN" />
      </div>
   HTML;
}))
   ->title('Home Page')
   ->route('/phpspa/template/{id:int}');