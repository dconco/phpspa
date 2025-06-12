<?php

use phpSPA\Component;
use function phpSPA\Component\createState;

include_once __DIR__ . '../../../app/core/Component/createState.php';

return (new Component(function ()
{
   $counter = createState('counter', 0);

   return <<<HTML
        <button onclick="phpspa.setState('counter', {$counter} + 1)">
            Clicks: {$counter}
        </button>
    HTML;
}))
   ->route('/phpspa/template/counter')
   ->title('Counter Component');