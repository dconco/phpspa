# `fmt()` Helper

<div class="grid cards" markdown>

-   :material-package-variant:{ .lg .middle } __Type Preservation__

    ---

    Pass any data type between components with perfect type safety

-   :material-code-braces:{ .lg .middle } __Zero Configuration__

    ---

    Works automatically with classes, arrays, objects, and primitives

-   :material-flash:{ .lg .middle } __Auto Serialization__

    ---

    Automatic encoding and decoding behind the scenes

-   :material-shield-check:{ .lg .middle } __Type Safe__

    ---

    Maintains exact type signatures across component boundaries

</div>

!!! success "New in v2.0.1"
    :material-new-box: Enhanced type preservation for component props

---

## :material-rocket-launch: Quick Start

```php
<?php
class User {
    public function __construct(
        public string $name,
        public int $age
    ) {}
}

$user = new User('John Doe', 25);
fmt($user);

// Pass to component - receives exact User instance!
return "<UserProfile>{$user}</UserProfile>";
```

```php
<?php
function UserProfile(User $children) {
    // Type-safe - receives actual User instance
    return <<<HTML
        <div>
            <h2>{$children->name}</h2>
            <p>Age: {$children->age}</p>
        </div>
    HTML;
}
```

---

## :material-cog: Multiple Arguments

```php
<?php
$product = new Product('iPhone', 999.99);
$options = ['badge' => 'New'];
$theme = 'dark';

fmt($product, $options, $theme);

return "<ProductCard theme='{$theme}' options='{$options}'>{$product}</ProductCard>";
```

---

## :material-information: What It Does

`fmt()` preserves exact data types when passing props between components:

- ✅ Custom classes remain their original type
- ✅ Arrays, objects, strings, numbers all preserved
- ✅ Works with interfaces, readonly properties, enums
- ✅ Automatic serialization/deserialization

!!! warning "Component Tags Only"
    Only works with component tag syntax: `<Component>{$data}</Component>`

