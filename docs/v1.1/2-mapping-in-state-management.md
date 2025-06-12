# State Management - map()

The `map()` function provides array iteration capabilities for state values.

## Usage

```php
$items = createState('cart.items', ['apple', 'banana']);

return <<<HTML
    <ul>
        {$items->map(fn ($item) => "<li>$item</li>")}
    </ul>
HTML;
```

## Behavior

- **Array State Only**: Only works when state value is an array
- **Automatic String Conversion**: Returns concatenated string result
- **Reactive**: Automatically updates when state changes

## Parameters

| Parameter   | Type       | Description                                                |
| ----------- | ---------- | ---------------------------------------------------------- |
| `$callback` | `callable` | Function that receives each item and returns string output |

## Return Value

Returns concatenated string result of all mapped items.

## Examples

### Basic List Rendering

```php
$todos = createState('todos', [
    'Buy milk',
    'Walk dog'
]);

return <<<HTML
    <ul>
        {$todos->map(fn ($todo) => "<li>$todo</li>")}
    </ul>
HTML;
```

### Complex Object Mapping

```php
$products = createState('products', [
    ['id' => 1, 'name' => 'Chair'],
    ['id' => 2, 'name' => 'Table']
]);

return <<<HTML
    <div>
        {$products->map(fn ($product) => "
            <div class='product' data-id='{$product['id']}'>
                {$product['name']}
            </div>
        ")}
    </div>
HTML;
```

## Notes

- For empty arrays, returns empty string
- Callback should always return string content
- Part of reactive state management system
