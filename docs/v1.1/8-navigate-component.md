# Navigate Component

## Overview

Client-side navigation component for SPA transitions:

```html
<Navigate path="/path" state="push" />
```

## Basic Usage

### Push State (Default)

```html
<Navigate path="/dashboard" />
```

### Replace State

```html
<Navigate path="/login" state="replace" />
```

## Component Attributes

| Attribute | Type   | Default  | Description                             |
| --------- | ------ | -------- | --------------------------------------- |
| `path`    | string | -        | **Required** target path                |
| `state`   | string | `"push"` | Navigation behavior (`push`, `replace`) |

## State Options

| Value     | Behavior            | History Impact           |
| --------- | ------------------- | ------------------------ |
| `push`    | Normal navigation   | Adds new entry (default) |
| `replace` | Replace current URL | No new history entry     |

## Examples

### After Form Submission

```html
<form>
  <!-- form fields -->
  <Navigate path="/thank-you" state="replace" />
</form>
```

### Conditional Navigation

```html
<?php if ($authenticated): ?>
  <Navigate path="/dashboard" />
<?php else: ?>
  <Navigate path="/login" state="replace" />
<?php endif; ?>
```

## Best Practices

1. **Placement Matters**

   ```html
   <!-- Good: At interaction points -->
   <button onclick="submitForm()">
     <Navigate path="/next" state="replace" />
   </button>

   <!-- Bad: Random placement -->
   <div>
     <Navigate path="/unexpected" /> <!-- Might trigger accidentally -->
   </div>
   ```

2. **State Selection**
   - Use `replace` after form submissions
   - Use `push` for main navigation

3. **Combine with Links**

   ```html
   <Link path="/cart">
     <Navigate path="/cart-sidebar" state="replace" />
     View Cart
   </Link>
   ```

## Technical Output

Renders as:

```html
<script data-type="phpspa/script">
  phpspa.navigate("/path", "push");
</script>
```

## Security

Automatically handles:

- Path encoding
- XSS protection
- Attribute sanitization

## Troubleshooting

| Issue             | Solution                         |
| ----------------- | -------------------------------- |
| Not navigating    | Check browser console for errors |
| Double navigation | Ensure single instance           |
| Wrong state       | Verify attribute spelling        |
