# Using phpSPA with Laravel

## Introduction

Laravel is excellent for building robust web applications, but sometimes you need dynamic, real-time updates in specific sections of your app without full page reloads. This is where phpSPA shines!

**Perfect Use Case: Chat Application**

Imagine you're building a chat app with two main sections:

1. **Users List** - Shows all available users
2. **Chat Messages** - Displays conversation with selected user

Instead of reloading the entire page when switching between users or receiving new messages, phpSPA allows you to update just the chat section dynamically while keeping the users list intact. This creates a smooth, SPA-like experience within your Laravel application.

## Installation

Add phpSPA to your Laravel project using Composer:

```bash
composer require dconco/phpspa
```

## Basic Setup

### 1. Laravel Route Setup

First, create your Laravel route to accept phpSPA internal requests:

```php
// routes/web.php
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
```

!!! note "phpSPA Internal Request"
To use Laravel routing, you don't have to specify custom route in each components.

### 2. Controller Implementation

In your Laravel controller, integrate phpSPA:

```php
<?php
// app/Http/Controllers/ChatController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpSPA\App;

class ChatController extends Controller
{
    public function index()
    {
        // Include phpSPA namespace
        use phpSPA\App;

        // Define the layout
        function Layout() {
            return <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chat Application</title>
                    <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
                    <style>
                        .chat-container { display: flex; height: 100vh; }
                    </style>
                </head>
                <body>
                    <div class="chat-container">
                        <div id="root">__CONTENT__</div>
                    </div>
                </body>
                </html>
            HTML;
        }

        // Initialize phpSPA
        $app = new App('Layout');
        $app->defaultTargetID('root');

        // Attach components
        $app->attach(require resource_path('views/components/UserComp.php'));
        $app->attach(require resource_path('views/components/ChatComp.php'));

        // Run the application
        $app->run();
    }
}
```

### 3. Create Components Directory

Create the components directory in your Laravel resources:

```bash
mkdir -p resources/views/components
```

## Component Files

### 4. Users Component

Create `resources/views/components/UserComp.php`:

```php
<?php
use phpSPA\Component;

include_once 'ChatComp.php'; // Include ChatComp for usage

return (new Component(fn () => <<<HTML
    <div id="users">
        <h3>Users Online</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;"
                onclick="phpspa.navigate('/chat?name=John')">
                👤 John Doe
            </li>
            <li style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;"
                onclick="phpspa.navigate('/chat?name=Jane')">
                👤 Jane Smith
            </li>
            <li style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;"
                onclick="phpspa.navigate('/chat?name=Mike')">
                👤 Mike Johnson
            </li>
            <li style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;"
                onclick="phpspa.navigate('/chat?name=Sarah')">
                👤 Sarah Wilson
            </li>
        </ul>
    </div>
    <div id="chat">
        <ChatComp default="true" />
    </div>
HTML))
  ->styleSheet(fn() => <<<CSS
    #users {
      width: 300px;
      border-right: 1px solid #ccc;
    }
    #chat {
      flex: 1;
    }
  CSS)

  ->route('/chat')
  ->method('GET')
  ->title('Chat Application');
```

### 5. Chat Component

Create `resources/views/components/ChatComp.php`:

```php
<?php

use phpSPA\Component;
use phpSPA\Http\Request;

function ChatComp(string $default = 'false', Request $request = new Request()) {
    if ($default == 'true') {
        return <<<HTML
            <div style="padding: 20px; text-align: center; color: #666;">
                <h3>Welcome to Chat!</h3>
                <p>Select a user from the left to start chatting</p>
            </div>
        HTML;
    }

    // Get the selected user
    $name = $request->get('name', 'User');

    return <<<HTML
        <div style="padding: 20px;">
            <h3>💬 Chat with {$name}</h3>
            <div style="border: 1px solid #ddd; height: 400px; padding: 10px; margin: 10px 0; overflow-y: auto; background: #f9f9f9;">
                <div style="margin-bottom: 10px;">
                    <strong>{$name}:</strong> Hey there! How are you doing?
                </div>
                <div style="margin-bottom: 10px; text-align: right;">
                    <strong>You:</strong> I'm doing great! Thanks for asking.
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>{$name}:</strong> That's wonderful to hear!
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <input type="text" placeholder="Type your message..."
                       style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <button style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Send
                </button>
            </div>
        </div>
    HTML;
}

$request = new Request();
$name = $request->get('name', 'User');

return (new Component('ChatComp'))
    ->title("Messaging {$name}")
    ->method('GET')
    ->targetID('chat'); // Update only the chat section
```

!!! note "Default Parameters"
Make sure to provide default values for function parameters when using components as tags (like `<ChatComp default="true" />`), since phpSPA won't pass the `$request` parameter automatically in this context.

## Important Considerations

### Blade Templates Limitation

⚠️ **Important**: You cannot use Blade syntax directly within phpSPA components because phpSPA outputs HTML immediately and exits the script. It doesn't return HTML to Laravel's view system.

### Using Blade with phpSPA

If you need to use Blade templates within your phpSPA components, you can capture the output using `ob_start()`:

```php
<?php
// In your controller method

use phpSPA\App;

class ChatController extends Controller
{
    public function index()
    {
        function Layout() {
            return <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Chat with Blade</title>
                    <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
                </head>
                <body>
                    <div id="root">__CONTENT__</div>
                </body>
                </html>
            HTML;
        }

        $app = new App('Layout');
        $app->defaultTargetID('root');

        $app->attach(require resource_path('views/components/BladeUserComp.php'));

        // Use output buffering to capture phpSPA output
        ob_start();
        $app->run();
        $phpSpaOutput = ob_get_clean();

        // Now you can use the output in a Blade template
        return view('chat.wrapper', [
            'phpSpaContent' => $phpSpaOutput,
            'pageTitle' => 'Dynamic Chat Application'
        ]);
    }
}
```

Create `resources/views/chat/wrapper.blade.php`:

```blade
@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1>{{ $pageTitle }}</h1>
                <p>Built with Laravel + phpSPA</p>

                <!-- phpSPA content will be inserted here -->
                {!! $phpSpaContent !!}
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .chat-container {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
@endpush
```

And update your component to use Blade data:

```php
<?php
// resources/views/components/BladeUserComp.php

use phpSPA\Component;

return (new Component(fn () => <<<HTML
    <div class="chat-container">
        <div id="users">
            <h3>Users from Laravel</h3>
            <!-- You can now access Laravel data here -->
            <p>Current user: {{ auth()->user()->name ?? 'Guest' }}</p>
            <ul class="list-unstyled">
                <li onclick="phpspa.navigate('/chat?name=John')">👤 John Doe</li>
                <li onclick="phpspa.navigate('/chat?name=Jane')">👤 Jane Smith</li>
            </ul>
        </div>
        <div id="chat">
            <div class="text-center p-4">
                <h3>Select a user to start chatting</h3>
                <small class="text-muted">Powered by phpSPA + Laravel</small>
            </div>
        </div>
    </div>
HTML))
->route('/chat')
->method('GET')
->title('Laravel Chat App');
```

## Best Practices

1. **File Organization**: Keep phpSPA components in `resources/views/components/` for consistency with Laravel conventions.

2. **Route Matching**: Ensure your phpSPA component routes match exactly with your Laravel routes and each component route is having their own normal HTTP method that they used in the routing.

3. **Method Matching**: Each component route is having their own normal HTTP method that they used in the routing and ensure Laravel routes accept this method via `Route::match()`.

4. **Error Handling**: Always provide default values for request parameters to prevent errors.

5. **Performance**: Use phpSPA for specific dynamic sections rather than entire pages for optimal performance.

## Next Steps

Now you have a fully functional chat application that combines Laravel's backend power with phpSPA's dynamic frontend capabilities! The users list stays static while the chat section updates dynamically based on user selection.

You can extend this by:

- Adding real-time WebSocket integration
- Implementing state management for message history
- Adding user authentication checks
- Creating more complex component interactions

Happy coding with Laravel + phpSPA! 🚀
