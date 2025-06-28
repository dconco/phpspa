# Using phpSPA with Existing PHP Projects

## Quick Start

Add modern SPA functionality to your existing PHP project without rebuilding everything. Perfect for modernizing specific sections like admin panels while keeping your current architecture.

**Example**: Transform your blog's comment moderation into a real-time interface while keeping post management unchanged.

## Installation

### Composer (Recommended)

```bash
composer require dconco/phpspa
```

### Manual

```bash
mkdir libs && cd libs
git clone https://github.com/dconco/phpspa.git
```

## Basic Setup

### 1. Update Configuration

Modify your existing `includes/config.php`:

```php
<?php
// Your existing config
define('DB_HOST', 'localhost');
// ... other configs

// Include phpSPA
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
```

### 2. Modify Existing Admin Page

Update your `admin/dashboard.php`:

```php
<?php
session_start();
require_once '../includes/config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Handle phpSPA requests
if (PHPSPA_ENABLED && (strpos($_SERVER['REQUEST_URI'], '/admin/comments') !== false)) {
    function Layout() {
        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <title>Admin Dashboard</title>
                <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
            </head>
            <body>
                <div id="root">__CONTENT__</div>
            </body>
            </html>
        HTML;
    }

    $app = new phpSPA\App('Layout');
    $app->defaultTargetID('root');

    // Make existing data available
    $GLOBALS['db_connection'] = get_db_connection();
    $GLOBALS['current_user'] = get_current_user();

    $app->attach(require COMPONENTS_PATH . 'CommentComp.php');
    $app->run();
}

// Your existing dashboard HTML for non-phpSPA requests
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
</head>
<body>
    <nav>
        <h2>Admin</h2>
        <ul>
            <li><a href="/admin/dashboard.php">Dashboard</a></li>
            <li><a href="#" onclick="phpspa.navigate('/admin/comments')">Comments</a></li>
        </ul>
    </nav>

    <div id="dynamic-content">
        <h3>Welcome back, <?php echo get_current_user()['name']; ?>!</h3>
        <button onclick="phpspa.navigate('/admin/comments')">Moderate Comments</button>
    </div>
</body>
</html>
```

## Create Components

### Comment Management Component

Create `components/CommentComp.php`:

```php
<?php
use phpSPA\Component;
use phpSPA\Http\Request;

function CommentComp(Request $request = new Request()) {
    $db = $GLOBALS['db_connection'];

    // Handle actions
    if ($request->method() === 'POST') {
        $action = $request('action');
        $comment_id = $request('comment_id');

        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$comment_id]);
            return '<div class="success">Comment approved!</div>';
        }
    }

    // Get comments
    $filter = $request('filter', 'pending');
    $stmt = $db->prepare("SELECT c.*, p.title as post_title FROM comments c
                         JOIN posts p ON c.post_id = p.id
                         WHERE c.status = ? ORDER BY c.created_at DESC");
    $stmt->execute([$filter]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $commentList = '';
    foreach ($comments as $comment) {
        $commentList .= <<<HTML
            <div class="comment-item">
                <h4>{$comment['author_name']} on "{$comment['post_title']}"</h4>
                <p>{$comment['content']}</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="comment_id" value="{$comment['id']}">
                    <button type="submit">Approve</button>
                </form>
            </div>
        HTML;
    }

    return <<<HTML
        <div class="comment-section">
            <h3>Comment Management</h3>
            <div class="filter-tabs">
                <PhpSPA.Component.Link to="/admin/comments?filter=pending">Pending</PhpSPA.Component.Link>
                <PhpSPA.Component.Link to="/admin/comments?filter=approved">Approved</PhpSPA.Component.Link>
            </div>
            <div class="comments-list">
                {$commentList}
            </div>
        </div>
    HTML;
}

return (new Component('CommentComp'))
    ->route('/admin/comments')
    ->method('POST')
    ->targetID('dynamic-content')
    ->title('Comment Management');
```

## Using Existing Functions

Integrate with your current codebase:

```php
function CommentComp(Request $request = new Request()) {
    // Use your existing functions
    require_once '../includes/functions.php';

    if ($request->method() === 'POST') {
        $action = $request('action');
        $comment_id = $request('comment_id');

        // Use existing functions
        if ($action === 'approve') {
            approve_comment($comment_id); // Your existing function
        }
    }

    $pending_comments = get_pending_comments(); // Your existing function

    // Component rendering...
}
```

## Gradual Migration

### Phase 1: Start Small

Convert one section first (e.g., comment moderation):

```php
// admin/comments.php
if (PHPSPA_ENABLED) {
    // Initialize phpSPA for this page only
    $app = new phpSPA\App('Layout');
    $app->attach(require COMPONENTS_PATH . 'CommentComp.php');
    $app->run();
}
// Fallback to existing HTML
```

### Phase 2: Expand

Add more sections once comfortable with the first implementation.

## Key Benefits

-  **No rewrites**: Keep existing code and database structure
-  **Gradual adoption**: Modernize one section at a time
-  **Existing functions**: Use your current helper functions and auth system
-  **Real-time updates**: Modern SPA experience where needed
-  **Fallback support**: Traditional pages still work

Perfect for adding modern interactivity to legacy PHP applications without disrupting existing functionality.
