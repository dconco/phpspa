# HTML Attribute Array to String Conversion

## Overview

```php
function HTMLAttrInArrayToString(array $HtmlAttr): string
```

Converts associative arrays to HTML attribute strings for component props.

## Basic Usage

```php
use function phpSPA\Component\HTMLAttrInArrayToString;

$attrs = [
    'class' => 'btn primary',
    'data-id' => '123',
    'disabled' => true
];

echo HTMLAttrInArrayToString($attrs);
```

*Output:*

```html
class="btn primary" data-id="123" disabled
```

## Conversion Rules

| Array Input                  | HTML Output           |
| ---------------------------- | --------------------- |
| `['name' => 'value']`        | `name="value"`        |
| `['disabled' => true]`       | `disabled`            |
| `['data-json' => '{"x":1}']` | `data-json="{"x":1}"` |
| `['class' => ['a', 'b']]`    | `class="a b"`         |

## Common Use Cases

### With Component Props

```php
function Button(array $props = []) {
    $attrs = HTMLAttrInArrayToString($props);
    return "<button {$attrs}>Click</button>";
}
```

### Handling Special Values

```php
$attributes = [
    'aria-expanded' => false,  // Omits attribute
    'style' => 'color: red;',  // Direct string
    'items' => '$products'    // Raw PHP variable
];

HTMLAttrInArrayToString($attributes);
```

## Edge Case Handling

1. **Boolean Attributes**

   ```php
   ['required' => true]  // → "required"
   ['hidden' => false]   // → "" (omitted)
   ```

2. **Array Values**

   ```php
   ['class' => ['active', 'rounded']] // → "class="active rounded""
   ```

3. **Escaping**

   ```php
   ['title' => 'Quote "mark"'] // → title="Quote &quot;mark&quot;"
   ```

## Best Practices

1. **Use with Spread Operator**

   ```php
   function Input(array $props = []) {
       $attrs = HTMLAttrInArrayToString($props);
       return "<input {$attrs}>";
   }
   ```

2. **Combine with Other Attributes**

   ```php
   $base = ['class' => 'field'];
   $extra = ['disabled' => true];
   HTMLAttrInArrayToString(array_merge($base, $extra));
   ```

## API Reference

### Parameters

- `array $HtmlAttr`  
  Associative array of attribute names/values

### Return Value

- `string`  
  Space-separated HTML attributes ready for insertion

## Examples

### Real-world Component Usage

```php
function Alert(array $props = []) {
    $attrs = HTMLAttrInArrayToString([
        'class' => array_merge(['alert'], $props['class'] ?? []),
        'role' => 'alert',
        ...$props
    ]);
    
    return "<div {$attrs}>{$props['children']}</div>";
}
```

## Next

- [Redirect Function](./7-redirect-function.md)
