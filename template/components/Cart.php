<?php

use phpSPA\Component;
use function phpSPA\Component\createState;

include_once __DIR__ . '../../../app/core/Component/createState.php';

return (new Component(function ()
{
    $items = createState('cart.items', [ [ 'name' => 'Bag', 'price' => 29.99 ] ]);
    $total = array_sum(array_column($items(), 'price'));

    return <<<HTML
        <div class="cart">
            <h3>Cart Total: \${$total}</h3>
            <ul>
                {$items->map(fn ($item) => "<li>{$item['name']}: \${$item['price']}</li>")}
            </ul>
            
            <h4>Add New Item</h4>
            <input type="text" placeholder="Item Name" id="name" />
            <input type="number" placeholder="Item Price" id="price" />
            <br />
            <br />
            <button onclick="addItem()">Add Item</button>
        </div>

        <script data-type="phpspa/script">
            function addItem(currentItems) {
                const name = document.getElementById('name').value;
                const price = parseFloat(document.getElementById('price').value);

                if (!name || isNaN(price)) {
                    alert('Please enter valid item details.');
                    return;
                }

                // Create new array with the added item
                const newItems = [...{$items}, { name, price }];
                
                phpspa.setState('cart.items', newItems)
                    .then(() => console.log('New item added to cart!'));
            }
        </script>
    HTML;
}))
   ->route('/phpspa/template/cart')
   ->title('View Cart');