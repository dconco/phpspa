<?php

use phpSPA\Component;

use phpSPA\Http\Request;
use function phpSPA\Component\import;
use function phpSPA\Component\createState;


return (new Component(function (Request $request): string
{
   $name = $request('name', 'dconco');
   $counter = createState('counter', 0);
   $icon = import(__DIR__ . '/../../site/assets/images/favicon.png');

   return <<<HTML
      <div>
         <img src="{$icon}" />
         <p>Welcome to my PHP SPA project! @$name</p>
         <br />
         <button onclick="phpspa.setState('counter', $counter + 1)">Counter: $counter</button>
         <Link to="./login#hashID" id="link-elem">GO TO LOGIN</Link>
      </div>
   HTML;
}))
   ->title('Home Page')
   ->route('/phpspa/template');