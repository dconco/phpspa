# Component Preloading & Multi-Section Layouts

!!! success "New in v2.0.4"
    :material-new-box: **Component preloading** for building complex, multi-section layouts

---

## 🚀 Overview

PhpSPA v2.0.4 introduces powerful features for building complex layouts where different sections update independently. Perfect for messaging apps, dashboards, and multi-panel interfaces.

**Key Features:**

- **`name()`** - Assign unique identifiers to components
- **`preload()`** - Load multiple components simultaneously on different target IDs
- **`exact()`** - Revert to default content when navigating away
- **`pattern()`** - Match routes using fnmatch patterns (e.g., `/blog/*`)
- **`method()`** - Accept multiple HTTP methods
- **`route()`** - Define multiple routes

---

## 📱 Real-World Example: WhatsApp Web Clone

Build a messaging interface with independent user list and chat sections.

### Layout

```php
<?php
function Layout() {
   return <<<HTML
      <div class="container">
         <section id="users"></section>
         <section id="chat">
            <div class="welcome">Welcome! Select a user to start chatting.</div>
         </section>
      </div>
   HTML;
}
```

### Components

#### Step 1: Create the User List Component

This component shows on the homepage and displays all available users to chat with.

```php
<?php
use PhpSPA\Component;

$userList = (new Component(function() {
   $users = [
      ['id' => 1, 'name' => 'John Doe'],
      ['id' => 2, 'name' => 'Jane Smith'],
   ];
   
   $html = '<div class="user-list"><h3>Chats</h3>';
   foreach ($users as $user) {
      $html .= "<a href='/chat/{$user['id']}'>{$user['name']}</a>";
   }
   return $html . '</div>';
}))
   ->route('/')           // Shows on homepage
   ->targetID('users')    // Renders in id="users"
   ->name('userList');    // Named for preloading
```

**Key points:**

- `route('/')` - This component renders on the homepage
- `targetID('users')` - Content goes into the `<section id="users">` element
- `name('userList')` - Assigns a unique name so other components can reference it

#### Step 2: Create the Chat Component

This component displays messages for a specific user. The magic happens with `preload()` and `exact()`.

```php
<?php
$chatView = (new Component(function(array $path) {
   $userId = $path['id'];
   $messages = getMessages($userId); // Your database function
   
   $html = "<div class='chat-header'>Chat with User {$userId}</div>";
   foreach ($messages as $msg) {
      $html .= "<div class='message'>{$msg['text']}</div>";
   }
   return $html;
}))
   ->route('/chat/{id: int}')      // Matches /chat/1, /chat/2, etc.
   ->targetID('chat')              // Renders in id="chat"
   ->preload('userList')           // CRITICAL: Also load user list
   ->exact();                      // Revert to welcome when navigating away
```

**Key points:**

- `route('/chat/{id: int}')` - Matches `/chat/1`, `/chat/2`, etc. with typed parameter
- `targetID('chat')` - Content goes into the `<section id="chat">` element
- `preload('userList')` - **This is the magic!** Also loads the user list component
- `exact()` - When navigating away from this route, revert to the default welcome message

#### Step 3: Register Components & Run

```php
<?php
$app = new App(Layout(...))
   ->attach($userList)
   ->attach($chatView)
   ->run();
```

### What Happens

**On `/` route:**
```
┌───────────────┬───────────────────┐
│ User List    │ Welcome Message │
│ • John Doe   │ Select a user   │
│ • Jane Smith │ to start...     │
└───────────────┴───────────────────┘
```

**On `/chat/1` route:**
```
┌───────────────┬───────────────────┐
│ User List    │ Chat with John  │
│ • John Doe   │ John: Hey!      │
│ • Jane Smith │ You: Hi!        │
└───────────────┴───────────────────┘
Both sections render because of preload()
```

**Navigate back to `/`:**
```
┌───────────────┬───────────────────┐
│ User List    │ Welcome Message │
│ • John Doe   │ Select a user   │
│ • Jane Smith │ to start...     │
└───────────────┴───────────────────┘
Chat reverts to welcome because of exact()
```

---

## ⚙️ Method Reference

### `name(string $value)`

Assigns unique identifier for preloading reference.

```php
<?php
$sidebar = (new Component(...))->name('sidebar');
```

### `preload(string ...$componentNames)`

Loads additional named components together.

```php
<?php
$main = (new Component(...))
   ->preload('sidebar', 'navbar', 'footer');
```

### `exact()`

Reverts to default content when route doesn't match.

```php
<?php
$profile = (new Component(...))
   ->route('/profile/{id: int}')
   ->exact(); // Reverts to default when navigating away
```

**Without `exact()`:** Stale content remains visible  
**With `exact()`:** Clean revert to default content

### `pattern()`

Enables fnmatch pattern matching.

```php
<?php
$blog = (new Component(...))
   ->route('/blog/*', '/articles/*')
   ->pattern();
```

| Pattern | Matches |
|---------|---------|
| `/blog/*` | `/blog/my-post`, `/blog/123` |
| `/user/*/posts/*` | `/user/123/posts/456` |

!!! tip "Extracting Values from Patterns"
    Pattern matching only validates routes. To extract values, use the `Request` object:
    
    ```php
    <?php
    use PhpSPA\Component;
    use PhpSPA\Http\Request;
    
    $blog = (new Component(function(Request $request) {
        $uri = $request->getUri();
        preg_match('/\/blog\/(.+)/', $uri, $matches);
        $slug = $matches[1] ?? null;

        return "<article>Post: {$slug}</article>";
    }))
       ->route('/blog/*')
       ->pattern();
    ```

### `method(string ...$methods)`

Accepts multiple HTTP methods.

```php
<?php
$form = (new Component(...))->method('GET', 'POST', 'PUT');
```

### `route(string ...$routes)`

Defines multiple routes to same component.

```php
<?php
$contact = (new Component(...))
   ->route('/contact', '/contact-us', '/get-in-touch');
```

---

## 🎨 More Examples

### Multi-Section Dashboard

```php
<?php
$navbar = (new Component(...))->name('navbar')->targetID('navbar');
$sidebar = (new Component(...))->name('sidebar')->targetID('sidebar');

$dashboard = (new Component(...))
   ->route('/dashboard')
   ->targetID('content')
   ->preload('navbar', 'sidebar');

$settings = (new Component(...))
   ->route('/dashboard/settings')
   ->targetID('content')
   ->preload('navbar', 'sidebar');
```

### Email Client

```php
<?php
$folders = (new Component(...))->name('folders')->targetID('folders');
$emailList = (new Component(...))->name('emailList')->targetID('emailList');

$emailViewer = (new Component(function(array $path) {
   return "<section>Email #{$path['id']}</section>";
}))
   ->route('/email/{id: int}')
   ->targetID('emailViewer')
   ->preload('folders', 'emailList')
   ->exact();
```

---

## ⚠️ Common Pitfalls

### ❌ Same `targetID` for Multiple Components

```php
<?php
$c1 = (new Component(...))->targetID('app');
$c2 = (new Component(...))->targetID('app'); // Replaces c1!
```

**Solution:** Use different target IDs.

### ❌ Forgetting `name()` for Preload

```php
<?php
$sidebar = (new Component(...))->targetID('sidebar');
$main = (new Component(...))->preload('sidebar'); // ERROR!
```

**Solution:** Set `name('sidebar')`.

### ❌ Not Using `exact()` for Dynamic Content

Without `exact()`, navigating away leaves stale content visible.

---

## 📚 Related Documentation

[:material-book-open-variant: Component Basics](../components/index.md){ .md-button }
[:material-routes: Advanced Routing](../routing/advanced-routing.md){ .md-button }
[:material-state-machine: State Management](../hooks/use-state.md){ .md-button }

---

**Need help?** [:material-github: Open an issue](https://github.com/dconco/phpspa/issues){ .md-button .md-button--primary }
