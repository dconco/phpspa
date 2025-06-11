<?php

use phpSPA\Component;
use phpSPA\Http\Request;
use function phpSPA\Component\createState;

include_once realpath(__DIR__ . '/../../app/core/Component/CreateState.php');

return (new Component(function (Request $request): string
{
   $name = $request('name', 'dconco');
   $counter = createState('counter', 0);
   $counterIncreament = "$counter" + 1;

   return <<<HTML
      <div>
         <p>Welcome to my PHP SPA project! @$name</p>
         <br />
         <button onclick="phpspa.setState('counter', $counterIncreament)">Counter: {$counter}</button>
         <Link to="./login#hashID" label="GO TO LOGIN" />
      </div>
   HTML;
}))
   ->title('Home Page')
   ->route('/phpspa/template/{id:int}');