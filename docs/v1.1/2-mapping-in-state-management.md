# State Management - map()

The `map()` function provides array iteration capabilities for state values.

## Usage

```php
<?php
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
<?php
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
<?php
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

---

## JavaScript Integration

Array states automatically convert to JSON when used in JavaScript context, enabling direct access to PHP state values in JavaScript.

### Accessing State in JavaScript

```php
<?php
$items = createState('cart.items', [
    ['id' => 1, 'name' => 'Product 1'],
    ['id' => 2, 'name' => 'Product 2']
]);
```

```html
<script>
    // Directly use PHP state as JavaScript object
    const currentItems = {$items};
    console.log(currentItems); 
    // Output: [{id: 1, name: 'Product 1'}, {id: 2, name: 'Product 2'}]
</script>
```

### Updating State

You can update state from either PHP or JavaScript:

#### From PHP

```php
<?php
// Update by calling state with new value
$items([...$items(), ['id' => 3, 'name' => 'New Product']]);
```

#### From JavaScript

```javascript
// Update using phpspa.setState()
phpspa.setState("cart.items", [
    ...currentItems, 
    {id: 3, name: 'New Product'}
]);
```

### Important Notes

1. **Automatic Reactivity**:
   - Both update methods will trigger UI updates automatically
   - No manual DOM manipulation needed

2. **State Reference**:
   - In JavaScript, always reference states using their exact keys
   - The `phpspa.setState()` method requires the full state key

3. **Performance**:
   - For complex objects, consider granular updates
   - Batch multiple updates when possible
