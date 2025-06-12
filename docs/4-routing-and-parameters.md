# 🧭 Routing and Parameters

phpSPA lets you define clean routes for your components — and yes, you can pass parameters too.

!!! info "Clean URLs Made Simple"
    Create beautiful, SEO-friendly URLs without the complexity of traditional routing systems.

Let's walk through how routing works, one step at a time.

## Basic Routing Concepts

### ✅ Basic Route

You've already seen this:

```php title="Simple Route Definition"
$home = new Component('Home');
$home->route("/");
```

This tells phpSPA:

!!! quote "Route Logic"
    "Render the `Home()` component when the user visits `/`."

Simple and straightforward!

### 🔢 Dynamic Routes (with Parameters)

You can pass values in the URL using curly braces `{}`:

=== "Single Parameter"

    ```php title="User Profile Route"
    $profile = new Component('Profile');
    $profile->route("/profile/{id}");
    ```

=== "Multiple Parameters"

    ```php title="Blog Post Route"
    $post = new Component('BlogPost');
    $post->route("/blog/{category}/{slug}");
    ```

=== "Optional Parameters"

    ```php title="Search with Optional Filters"
    $search = new Component('Search');
    $search->route("/search/{term?}");
    ```

!!! example "URL Examples"
    - `/profile/42` → `id` = "42"
    - `/blog/tech/php-spa-guide` → `category` = "tech", `slug` = "php-spa-guide"
    - `/search/` or `/search/laravel` → `term` = null or "laravel"

## Accessing Route Parameters

### 🧠 The `$path` Parameter

phpSPA injects route parameters into your component automatically using a special `$path` argument:

```php title="Accessing Route Parameters"
<?php
function Profile(array $path = []) {
    $userId = $path["id"] ?? "guest";
    
    return <<<HTML
        <div class="profile-card">
            <h2>User Profile</h2>
            <p>User ID: <strong>$userId</strong></p>
        </div>
    HTML;
}
```

#### 📋 Parameter Rules

| Rule         | Requirement                  | Example                                  |
| ------------ | ---------------------------- | ---------------------------------------- |
| **Name**     | Must be `$path`              | `function MyComponent(array $path = [])` |
| **Type**     | Array type hint recommended  | `array $path = []`                       |
| **Default**  | Must have default value      | `= []`                                   |
| **Position** | Can be anywhere in arguments | First, last, or middle                   |

!!! tip "Best Practice"
    Always provide default values and use null coalescing (`??`) for safer parameter access.

### 📬 Query Parameters (GET Parameters)

To access query strings (e.g., `/search?term=apple&page=2`), use a `$request` parameter:

```php title="Handling Query Parameters"
<?php
use phpSPA\Http\Request;

function Search(array $path = [], Request $request = null) {
    $request = $request ?? new Request();
    
    $term = $request("term") ?? "";
    $page = (int)($request("page") ?? 1);
    
    return <<<HTML
        <div class="search-results">
            <h2>Search Results</h2>
            <p>Searching for: <em>$term</em></p>
            <p>Page: $page</p>
        </div>
    HTML;
}
```

!!! info "Request Parameter Access"
    `$request("key")` works like `$_GET["key"]` but with better error handling and cleaner syntax.

#### 📋 Request Rules

| Rule        | Requirement                     | Example                   |
| ----------- | ------------------------------- | ------------------------- |
| **Name**    | Must be `$request`              | `Request $request = null` |
| **Type**    | `phpSPA\Http\Request` type hint | Import the class first    |
| **Default** | Provide default instance        | `= new Request()`         |
| **Usage**   | Call like a function            | `$request("param_name")`  |

## Advanced Routing Patterns

### 🧩 Component Nesting

When calling a component from inside another component, you need default values:

=== "Reusable Component"

    ```php title="Reusable UI Component"
    <?php
    function UserCard(array $path = [], Request $request = null) {
        $request = $request ?? new Request();
        $userId = $path["id"] ?? "unknown";
        
        return <<<HTML
            <div class="user-card">
                <h3>User: $userId</h3>
                <p>Status: Active</p>
            </div>
        HTML;
    }
    ```

=== "Parent Component"

    ```php title="Dashboard Using Nested Components"
    <?php
    function Dashboard(array $path = [], Request $request = null) {
        return <<<HTML
            <div class="dashboard">
                <h1>Admin Dashboard</h1>
                <div class="widgets">
                    {UserCard()}
                    {UserCard()}
                </div>
            </div>
        HTML;
    }
    ```

### 🎯 Complete Example

Here's a comprehensive routing example:

```php title="Complete Routing Example" linenums="1"
<?php
use phpSPA\App;
use phpSPA\Component;
use phpSPA\Http\Request;

// User profile component with route and query parameters
function UserProfile(array $path = [], Request $request = null) {
    $request = $request ?? new Request();
    
    // Extract route parameters
    $userId = $path["id"] ?? "unknown";
    
    // Extract query parameters
    $tab = $request("tab") ?? "profile";
    $edit = $request("edit") === "true";
    
    $editMode = $edit ? "Edit Mode: ON" : "View Mode";
    
    return <<<HTML
        <div class="user-profile">
            <h1>User Profile: $userId</h1>
            <p>Active Tab: <strong>$tab</strong></p>
            <p>Mode: <em>$editMode</em></p>
            
            <nav class="profile-tabs">
                <a href="/user/$userId?tab=profile">Profile</a>
                <a href="/user/$userId?tab=settings">Settings</a>
                <a href="/user/$userId?tab=activity">Activity</a>
            </nav>
        </div>
    HTML;
}

// Register the component
$app = new App($layout);
$userProfile = new Component('UserProfile');
$userProfile->route("/user/{id}");
$app->attach($userProfile);
?>
```

## 🧪 Testing Your Routes

### Example URLs and Results

| URL                                | Route Params | Query Params                        | Result        |
| ---------------------------------- | ------------ | ----------------------------------- | ------------- |
| `/user/123`                        | `id` = "123" | None                                | Basic profile |
| `/user/123?tab=settings`           | `id` = "123" | `tab` = "settings"                  | Settings tab  |
| `/user/456?tab=activity&edit=true` | `id` = "456" | `tab` = "activity", `edit` = "true" | Edit mode     |

### Quick Test Code

```php title="Test Your Routes"
<?php
// Test URL: /user/42?tab=settings&edit=true
function UserProfile(array $path = [], Request $request = null) {
    $request = $request ?? new Request();
    
    var_dump([
        'path' => $path,
        'query' => [
            'tab' => $request("tab"),
            'edit' => $request("edit")
        ]
    ]);
    
    return "<h1>Debug output above</h1>";
}
```

## 🚀 Route Best Practices

!!! tip "Routing Tips"
    - **Use descriptive parameter names**: `{userId}` instead of `{id}`
    - **Validate parameters**: Always check if required parameters exist
    - **Provide defaults**: Use null coalescing for optional parameters
    - **Keep routes RESTful**: Follow `/resource/{id}` patterns
    - **Handle edge cases**: What happens with invalid IDs?

## 🔧 What's Next?

Now that you understand basic routing, let's explore advanced route patterns and parameter validation.

[Route Patterns & Parameter Types :material-arrow-right:](./5-route-patterns-and-param-types.md){ .md-button .md-button--primary }

---

!!! question "Common Questions"
    **Q: Can I use multiple parameters in one route?**  
    A: Yes! Use `/blog/{category}/{slug}` format.

    **Q: How do I handle optional parameters?**  
    A: Use `{param?}` syntax and check with `?? "default"` in your component.
    
    **Q: Can I access POST data?**  
    A: Yes, the `$request` object provides access to all HTTP data.
