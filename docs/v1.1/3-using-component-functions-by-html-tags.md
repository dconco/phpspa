# Using Component Functions via HTML Tags

## Overview

Components can now be accessed directly through HTML-style tags in templates:

```html
<Component />  <!-- Self-closing -->
<Component></Component>  <!-- Block version -->
```

## Basic Usage

1. **Component Tag Syntax**

   ```html
   <!-- In your template file -->
   <Header title="My App" />
   
   <!-- Renders as PHP: -->
   <?php Component\Header(['title' => 'My App']); ?>
   ```

2. **Props Passing**

   ```html
   <UserProfile 
      name="John Doe"
      age="25"
      scores="[90, 85, 95]"
   />
   ```

## Advanced Features

### Dynamic Content

```html
<Card>
   <h2>Child content</h2>
   <p>Supports nested HTML</p>
</Card>
```

### Namespaced Components

```html
<PhpSPA.Component.DataTable rows="$users" />
```

## Conversion Rules

| HTML Attribute   | PHP Equivalent               |
| ---------------- | ---------------------------- |
| `name="value"`   | `'name' => 'value'`          |
| `:items="$data"` | `'items' => $data` (raw PHP) |
| `slot-content`   | Becomes children elements    |

## Examples

### Basic Component

```html
<!-- Template -->
<Alert type="success">Operation completed!</Alert>
```

### With Dynamic Props

```html
<Dropdown 
   options="$optionsState"
   default="Select user"
/>
```

## Best Practices

1. **Always close tags** - Either self-closing (`<Comp />`) or with end tag
2. **Prefix custom components** - Use `<PhpSPA.Component.*>` for clarity

## See Also

- [Component Basics](./4-component-basics.md)
- [Link Component](./5-link-component.md)
