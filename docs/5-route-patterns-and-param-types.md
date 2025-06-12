# 🧰 Route Patterns & Param Types

phpSPA's routing system goes beyond just matching static or dynamic URLs — it supports **route patterns** (like wildcards) and even **typed parameters** (so you can validate values right in the route). Let's break them down.

---

## 🔀 Route Patterns

Sometimes, you don't want to match a specific path like `/admin/dashboard` — you want a pattern like `/admin/*` that catches anything under `/admin`.

phpSPA lets you write that like this:

=== "Basic Pattern"

    ```php
    $adminPanel = new Component('Admin');
    $adminPanel->route("pattern: /admin/*"); // (1)
    ```

    1. The `pattern:` prefix tells phpSPA to use pattern matching mode

=== "Alternative Syntax"

    ```php
    // You can also chain it for readability
    $adminPanel = new Component('Admin')
        ->route("pattern: /admin/*");
    ```

!!! info "Pattern Matching Engine"
    When the route starts with `pattern:`, phpSPA switches to **pattern matching mode** using `fnmatch()` behind the scenes.

### 🤔 What can you do with route patterns?

Here are some powerful examples:

=== "Admin Routes"

    | Pattern                  | Matches                                  |
    | ------------------------ | ---------------------------------------- |
    | `pattern: /admin/*`      | `/admin`, `/admin/users`, `/admin/42`    |
    | `pattern: /admin/*/edit` | `/admin/users/edit`, `/admin/posts/edit` |

=== "File Routes"

    | Pattern                 | Matches                                  |
    | ----------------------- | ---------------------------------------- |
    | `pattern: /blog/*.html` | `/blog/post.html`, `/blog/article.html`  |
    | `pattern: /files/*.zip` | `/files/latest.zip`, `/files/backup.zip` |

=== "API Routes"

    | Pattern               | Matches                             |
    | --------------------- | ----------------------------------- |
    | `pattern: /api/v*/*`  | `/api/v1/users`, `/api/v2/posts`    |
    | `pattern: /docs/*.md` | `/docs/readme.md`, `/docs/guide.md` |

!!! tip "Pro Tip: Pattern Use Cases"
    This is super useful for:

    - 🏗️ **Grouping routes** - Handle entire sections with one component
    - 🎯 **Catching unmatched paths** - Create fallback handlers
    - 🔐 **Building admin sections** - Minimal routing logic for protected areas
    - 📁 **File serving** - Match specific file types or directories

---

## 🔡 Parameter Types

phpSPA lets you enforce **types** on route parameters — so only valid values match the route. Here's how it works.

### ✅ Basic Typed Parameters

=== "Integer Parameters"

    ```php
    $profile = new Component('Profile');
    $profile->route("/profile/{id: int}"); // (1)
    ```

    1. Only numeric values like `42` will match this route

=== "String Parameters"

    ```php
    $blog = new Component('BlogPost');
    $blog->route("/blog/{slug: string}"); // (1)
    ```

    1. Matches any text value in the slug parameter

=== "Boolean Parameters"

    ```php
    $settings = new Component('Settings');
    $settings->route("/settings/{active: bool}"); // (1)
    ```

    1. Accepts `true`, `false`

### 📚 Complete Parameter Types Reference

| Type              | Description                          | Example Values       |
| ----------------- | ------------------------------------ | -------------------- |
| `int` / `integer` | Must be a number                     | `42`, `0`, `-10`     |
| `bool`            | Boolean values                       | `true`, `false`      |
| `string`          | Any plain text                       | `hello`, `user-name` |
| `alpha`           | Only letters                         | `John`, `admin`      |
| `alphanum`        | Letters and numbers only             | `user123`, `item42`  |
| `json`            | Must be valid JSON (auto-decoded)    | `{"key":"value"}`    |
| `array`           | Accepts JSON arrays or query strings | `[1,2,3]`, `a,b,c`   |

### 🤯 Advanced Type Examples

=== "JSON Parameters"

    ```php
    $api = new Component('APIHandler');
    $api->route("/api/process/{data: json}"); // (1)
    ```

    1. Automatically validates and decodes JSON data

=== "Array Parameters"

    ```php
    $search = new Component('Search');
    $search->route("/search/{filters: array}"); // (1)
    ```

    1. Handles both JSON arrays and comma-separated values

=== "Complex Validation"

    ```php
    $user = new Component('UserManager');
    $user->route("/users/{id: int}/posts/{status: alpha}"); // (1)
    ```

    1. Both parameters must match their types for the route to activate

!!! example "Real-world Example"
    ```php
    // E-commerce product route with validation
    $product = new Component('ProductDetail');
    $product->route("/products/{id: int}/reviews/{verified: bool}");

    // ✅ Matches: /products/123/reviews/true
    // ❌ Skips: /products/abc/reviews/maybe
    ```

---

## 🧠 Why Use Typed Parameters?

!!! success "Benefits of Type Validation"

    **Cleaner Code**
    :   No need to manually check `is_numeric()` or validate input types
    
    **Better Routing Control**
    :   Routes with mismatched types are automatically skipped
    
    **Safer Components**
    :   Your components receive exactly the data types they expect
    
    **Intentional Design**
    :   Routes explicitly declare what kind of data they handle

=== "Without Types"

    ```php
    <?php
    // Old way - manual validation needed
    $profile->route("/profile/{id}");
    
    // In your component:
    if (!is_numeric($params['id'])) {
        throw new InvalidArgumentException('ID must be numeric');
    }
    ```

=== "With Types"

    ```php
    // New way - automatic validation
    $profile->route("/profile/{id: int}");
    
    // In your component:
    // $params['id'] is guaranteed to be an integer!
    ```

---

## 🛑 What Happens on Type Mismatch?

!!! warning "Route Skipping Behavior"
    If the parameter value doesn't match the expected type, phpSPA **ignores the route entirely** — it won't run the component. This prevents accidental matches and invalid behavior.

```php
<?php
$user = new Component('UserProfile');
$user->route("/user/{id: int}");

// ✅ /user/123 → Component runs with id=123
// ❌ /user/john → Route skipped, component doesn't run
// ❌ /user/12.5 → Route skipped (not an integer)
```

---

## 🔗 Combining Patterns and Types

!!! tip "Pro Feature: Pattern + Type Combo"
    You can use typed parameters and patterns together — they're fully compatible!

=== "Admin with Types"

    ```php
    $adminUsers = new Component('AdminUserManager');
    $adminUsers->route("pattern: /admin/users/{id: int}/*");
    ```

=== "File Processing"

    ```php
    $fileProcessor = new Component('FileHandler');
    $fileProcessor->route("pattern: /files/{type: alpha}/*.{ext: alpha}");
    ```

=== "API Versioning"

    ```php
    $apiHandler = new Component('VersionedAPI');
    $apiHandler->route("pattern: /api/v{version: int}/*");
    ```

---

!!! quote "Quick Recap"
    - 🔀 **Patterns** use `pattern:` prefix for wildcard matching
    - 🔡 **Types** validate parameters automatically
    - 🛡️ **Mismatches** cause routes to be skipped safely
    - 🔗 **Combination** of patterns and types gives maximum flexibility

---

➡️ **Next:** [Loading Events](./6-loading-events.md)
