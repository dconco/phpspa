# Link Component

## Standard Usage

```html
<PhpSPA.Component.Link 
   to="/about" 
   class="nav-link"
>
   About Page
</PhpSPA.Component.Link>
```

## Function Signature

```php
function Link(
    string $to, 
    string $children, 
    string ...$HtmlAttr
): string;
```

## Props Reference

| Prop           | Type   | Required | Description                |
| -------------- | ------ | -------- | -------------------------- |
| `to`           | string | Yes      | Target path or URL         |
| `children`     | string | Yes      | Link text/content          |
| `...$HtmlAttr` | string | No       | Additional HTML attributes |

## Examples

### Basic Link

```html
<PhpSPA.Component.Link to="/contact">
   Contact Us
</PhpSPA.Component.Link>
```

### With Additional Attributes

```html
<PhpSPA.Component.Link 
   to="/privacy" 
   class="text-blue-500" 
   target="_blank"
>
   Privacy Policy
</PhpSPA.Component.Link>
```

### Dynamic Path

```php
<?php $productId = 123; ?>
<PhpSPA.Component.Link to="/product/$productId">
   View Product
</PhpSPA.Component.Link>
```

## Deprecated Usage {#deprecated}

### Old Syntax (v1.0)

```html
<Link href="/old-path">Old Link</Link>
```

### Migration Guide

1. Replace `href` with `to`
2. Add full namespace prefix
3. Update any documentation references

```diff
- <Link href="/about">About</Link>
+ <PhpSPA.Component.Link to="/about">About</PhpSPA.Component.Link>
```

## Best Practices

1. **Always use full namespace** - `<PhpSPA.Component.Link>`
2. **Escape dynamic content** - Use `htmlspecialchars()` for user-generated text
3. **External links** - Still requires `target="_blank"`

## Troubleshooting

| Issue               | Solution                    |
| ------------------- | --------------------------- |
| Link not working    | Verify namespace is correct |
| Attributes missing  | Check `...$HtmlAttr` spread |
| Deprecation warning | Update to new syntax        |

## NEXT

- [HTML Attribute In Array To String Function](./6-html-attr-in-array-to-string-function.md)
