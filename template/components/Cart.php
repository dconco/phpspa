<?php

use phpSPA\Component;
use function phpSPA\Component\createState;

include_once __DIR__ . '../../../app/core/Component/createState.php';

return (new Component(function ()
{
    $items = createState('cart.items', [ 'ss', 'ffk' ]);
    $total = array_sum(array_column($items(), 'price'));

    return <<<HTML
        <div class="cart">
            <h3>Cart Total: \${$total}</h3>
            <ul>
                {$items->map(fn ($item) => "<li>$item</li>")}
            </ul>
            <button onclick="addItem()">Add Sample</button>
        </div>

        <script data-type="phpspa/script">
            function addItem() {
                phpspa.setState('cart.items', [
                    ...{"{$items}"},
                    {name: 'Sample', price: 9.99}
                ]);
            }
        </script>
    HTML;
}))
   ->route('/phpspa/template/cart')
   ->title('View Cart');