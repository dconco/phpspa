# Component Basics

## Core Concepts

Components are reusable PHP functions that generate HTML. They follow these principles:

1. **PHP Functions as Components**

   ```php
   function Button(array $props = []) {
       return "<button class='btn'>{$props['text']}</button>";
   }
   ```

2. **HTML Tag Access** (New in v1.1.0)

   ```html
   <!-- Template file -->
   <Button text="Click Me" />
   ```

## Creating Components

### File Structure

```
components/
├── Button.php
├── Header.php
└── ...
```

### Minimal Component Example

```php
// components/Alert.php
namespace PhpSPA\Component;

function Alert(array $props = []) {
    $message = htmlspecialchars($props['message'] ?? '');
    return "<div class='alert'>{$message}</div>";
}
```

## Using Components

### Method 1: Traditional PHP

```php
<?php 
use PhpSPA\Component\Alert;
echo Alert(message: 'Warning!');
?>
```

### Method 2: HTML Tags (Recommended)

```html
<Alert message="Warning!" />
```

### With Children Content

```html
<Card>
   <h2>Title</h2>
   <p>Body content</p>
</Card>
```

*Converts to PHP:*

```php
<?php 
Card(children: '<h2>Title</h2><p>Body content</p>');
?>
```

## Props Handling

### Basic Props

```html
<Avatar 
   src="user.jpg"
   size="100"
/>
```

### Array Props

```php
<?php
$user = ['name' => 'John', 'id' => 42];
?>
<Profile user="$user" />
```

## Best Practices

1. **Namespacing**

   ```html
   <PhpSPA.Component.Navbar />
   ```

2. **Props Validation**

   ```php
   function Avatar(array $props = []) {
       $props['size'] = $props['size'] ?? 50; // Default
       // ...
   }
   ```

3. **Security**

   ```php
   // Always escape output
   htmlspecialchars($props['user_input']);
   ```

## Troubleshooting

| Issue               | Solution                                     |
| ------------------- | -------------------------------------------- |
| Component not found | Check namespace and autoloading              |
| Props not received  | Verify array keys match attribute names      |
| HTML escaping       | Use `htmlspecialchars()` for dynamic content |

## Next Steps

- [Link Component](./5-link-component.md)
