# Using phpSPA with Symfony

## Introduction

Symfony provides excellent structure for enterprise-grade web applications, but sometimes you need dynamic, real-time updates in specific sections without full page reloads. This is where phpSPA shines!

**Perfect Use Case: Project Management Dashboard**

Imagine you're building a project management app with multiple sections:

1. **Project List** - Shows all active projects
2. **Task Board** - Displays tasks in different columns (To Do, In Progress, Done)
3. **Team Members** - Shows team assignments and availability

Instead of reloading the entire page when moving tasks between columns or updating project status, phpSPA allows you to update just the task board section dynamically while keeping the project list and team members intact. This creates a smooth, SPA-like experience within your Symfony application.

## Installation

Add phpSPA to your Symfony project using Composer:

```bash
composer require dconco/phpspa
```

## Basic Setup

### 1. Symfony Route Setup

First, create your Symfony route to accept phpSPA internal requests:

```php
// config/routes.yaml
project_dashboard:
    path: /dashboard
    controller: App\Controller\DashboardController::index
    methods: [GET, POST, PHPSPA_GET]
```

Or using annotations in your controller:

```php
<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'project_dashboard', methods: ['GET', 'POST', 'PHPSPA_GET'])]
    public function index(): Response
    {
        // phpSPA integration code will go here
    }
}
```

!!! note "phpSPA Internal Request"
The `PHPSPA_GET` method is used by phpSPA for internal routing within Symfony. This allows phpSPA to make requests that Symfony can accept, while each component route maintains its own normal HTTP method for regular routing functionality.

### 2. Controller Implementation

In your Symfony controller, integrate phpSPA:

```php
<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use phpSPA\App;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'project_dashboard', methods: ['GET', 'POST', 'PHPSPA_GET'])]
    public function index(): Response
    {
        // Define the layout
        function Layout() {
            return <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Project Dashboard</title>
                    <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script>
                </head>
                <body>
                    <div id="root">__CONTENT__</div>
                </body>
                </html>
            HTML;
        }

        // Initialize phpSPA
        $app = new App('Layout');
        $app->defaultTargetID('root');

        // Attach components
        $app->attach(require $this->getParameter('kernel.project_dir') . '/src/Components/ProjectComp.php');
        $app->attach(require $this->getParameter('kernel.project_dir') . '/src/Components/TaskBoardComp.php');

        // Run the application
        $app->run();

        // This return will never be reached due to phpSPA's exit behavior
        return new Response();
    }
}
```

### 3. Create Components Directory

Create the components directory in your Symfony project:

```bash
mkdir -p src/Components
```

## Component Files

### 4. Project Component

Create `src/Components/ProjectComp.php`:

```php
<?php
use phpSPA\Component;

include_once 'TaskBoardComp.php'; // Include TaskBoardComp for usage

return (new Component(fn () => <<<HTML
    <div class="project-sidebar">
        <h4>Active Projects</h4>
        <div class="project-list">
            <PhpSPA.Component.Link to="/dashboard?project=ecommerce">
                E-commerce Platform (15 tasks)
            </PhpSPA.Component.Link>
            <PhpSPA.Component.Link to="/dashboard?project=mobile-app">
                Mobile Application (8 tasks)
            </PhpSPA.Component.Link>
        </div>

        <h5>Team Members</h5>
        <div class="team-list">
            <span>John (Available)</span>
            <span>Sarah (Busy)</span>
        </div>
    </div>
    <div class="main-content">
        <div id="task-board">
            <TaskBoardComp default="true" />
        </div>
    </div>
HTML))
->route('/dashboard')
->method('GET')
->title('Project Dashboard');
```

### 5. Task Board Component

Create `src/Components/TaskBoardComp.php`:

```php
<?php

use phpSPA\Component;
use phpSPA\Http\Request;

function TaskBoardComp(string $default = 'false', Request $request = new Request()) {
    if ($default == 'true') {
        return <<<HTML
            <div class="task-board">
                <h3>Select a project to view tasks</h3>
                <p>Choose a project from the sidebar to see the task board</p>
            </div>
        HTML;
    }

    // Get the selected project
    $project = $request->get('project', 'unknown');
    $projectNames = [
        'ecommerce' => 'E-commerce Platform',
        'mobile-app' => 'Mobile Application',
        'website' => 'Company Website',
        'api' => 'REST API'
    ];

    $projectName = $projectNames[$project] ?? 'Unknown Project';

    return <<<HTML
        <div class="task-board">
            <h3>{$projectName}</h3>

            <div class="task-columns">
                <div class="column">
                    <h5>To Do</h5>
                    <div class="task-card" onclick="phpspa.navigate('/dashboard?project={$project}&task=1')">
                        <strong>Setup Database Schema</strong>
                        <p>Create tables for user management</p>
                    </div>
                </div>

                <div class="column">
                    <h5>In Progress</h5>
                    <div class="task-card" onclick="phpspa.navigate('/dashboard?project={$project}&task=3')">
                        <strong>Implement Authentication</strong>
                        <p>JWT token-based authentication</p>
                    </div>
                </div>

                <div class="column">
                    <h5>Done</h5>
                    <div class="task-card">
                        <strong>Project Setup</strong>
                        <p>Initialize Symfony project</p>
                    </div>
                </div>
            </div>
        </div>
    HTML;
}

$request = new Request();
$project = $request->get('project', 'unknown');

return (new Component('TaskBoardComp'))
    ->title("Tasks - " . ucfirst($project))
    ->method('POST')
    ->targetID('task-board'); // Update only the task board section
```

!!! note "Default Parameters"
Make sure to provide default values for function parameters when using components as tags (like `<TaskBoardComp default="true" />`), since phpSPA won't pass the `$request` parameter automatically in this context.

## Symfony Service Integration

### Using Symfony Services in Components

You can access Symfony services within your phpSPA components by passing them through the controller:

```php
<?php
// src/Controller/DashboardController.php

use App\Service\ProjectService;
use App\Service\TaskService;

class DashboardController extends AbstractController
{
    public function __construct(
        private ProjectService $projectService,
        private TaskService $taskService
    ) {}

    #[Route('/dashboard', name: 'project_dashboard', methods: ['GET', 'POST', 'PHPSPA_GET'])]
    public function index(): Response
    {
        // Pass services to components
        $GLOBALS['symfony_services'] = [
            'projectService' => $this->projectService,
            'taskService' => $this->taskService,
            'security' => $this->container->get('security.helper')
        ];

        // ... rest of phpSPA setup
    }
}
```

Then in your components:

```php
<?php
// src/Components/TaskBoardComp.php

function TaskBoardComp(string $default = 'false', Request $request = new Request()) {
    // Access Symfony services
    $taskService = $GLOBALS['symfony_services']['taskService'] ?? null;
    $projectService = $GLOBALS['symfony_services']['projectService'] ?? null;

    if ($taskService && $projectService) {
        $project = $request->get('project');
        $tasks = $taskService->getTasksByProject($project);
        $projectData = $projectService->getProject($project);

        // Use real data from Symfony services
        // ... component logic
    }
}
```

## Important Considerations

### Twig Templates Limitation

⚠️ **Important**: You cannot use Twig syntax directly within phpSPA components because phpSPA outputs HTML immediately and exits the script. It doesn't return HTML to Symfony's response system.

### Using Twig with phpSPA

If you need to use Twig templates within your phpSPA components, you can render them separately and capture the output:

```php
<?php
// src/Controller/DashboardController.php

use Twig\Environment;

class DashboardController extends AbstractController
{
    public function __construct(
        private Environment $twig
    ) {}

    #[Route('/dashboard', name: 'project_dashboard', methods: ['GET', 'POST', 'PHPSPA_GET'])]
    public function index(): Response
    {
        function Layout() {
            return <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Dashboard with Twig</title>
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

        // Pass Twig to components
        $GLOBALS['twig'] = $this->twig;

        $app->attach(require $this->getParameter('kernel.project_dir') . '/src/Components/TwigProjectComp.php');

        // Use output buffering to capture phpSPA output
        ob_start();
        $app->run();
        $phpSpaOutput = ob_get_clean();

        // Now you can use the output in a Twig template
        return $this->render('dashboard/wrapper.html.twig', [
            'phpSpaContent' => $phpSpaOutput,
            'pageTitle' => 'Dynamic Project Dashboard'
        ]);
    }
}
```

Create `templates/dashboard/wrapper.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ pageTitle }}{% endblock %}

{% block body %}
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <PhpSPA.Component.Link class="navbar-brand" to="#">{{ pageTitle }}</PhpSPA.Component.Link>
                <span class="navbar-text">
                    Welcome, {{ app.user.username|default('Guest') }}
                </span>
            </div>
        </nav>

        <!-- phpSPA content will be inserted here -->
        {{ phpSpaContent|raw }}
    </div>
{% endblock %}

{% block stylesheets %}
    <style>
        .dashboard-wrapper {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
{% endblock %}
```

And update your component to use Twig:

```php
<?php
// src/Components/TwigProjectComp.php

use phpSPA\Component;

return (new Component(fn () => {
    $twig = $GLOBALS['twig'] ?? null;

    $twigContent = '';
    if ($twig) {
        $twigContent = $twig->render('dashboard/project_sidebar.html.twig', [
            'projects' => [
                ['id' => 'ecommerce', 'name' => 'E-commerce Platform', 'tasks' => 15],
                ['id' => 'mobile-app', 'name' => 'Mobile Application', 'tasks' => 8],
                ['id' => 'website', 'name' => 'Company Website', 'tasks' => 3],
                ['id' => 'api', 'name' => 'REST API', 'tasks' => 12],
            ]
        ]);
    }

    return <<<HTML
        <div class="dashboard-wrapper">
            {$twigContent}
            <div id="task-board">
                <div class="text-center p-4">
                    <h3>Select a project to view tasks</h3>
                    <small class="text-muted">Powered by phpSPA + Symfony + Twig</small>
                </div>
            </div>
        </div>
    HTML;
}))
->route('/dashboard')
->method('GET')
->title('Symfony Dashboard');
```

Create `templates/dashboard/project_sidebar.html.twig`:

```twig
<div class="col-md-3 project-sidebar">
    <div class="p-3">
        <h4>Active Projects</h4>
        <div class="list-group">
            {% for project in projects %}
                <PhpSPA.Component.Link to="/dashboard?project={{ project.id }}" class="list-group-item list-group-item-action">
                    {{ project.name }}
                    <small class="d-block text-muted">{{ project.tasks }} tasks remaining</small>
                </PhpSPA.Component.Link>
            {% endfor %}
        </div>
    </div>
</div>
```

## Best Practices

1. **File Organization**: Keep phpSPA components in `src/Components/` to follow Symfony's structure conventions.

2. **Route Matching**: Ensure your phpSPA component routes match exactly with your Symfony routes and use `PHPSPA_GET` method for Symfony routing compatibility.

3. **Method Matching**: Each component route uses its own normal HTTP method (GET, POST, etc.) while `PHPSPA_GET` is only used in Symfony routing for phpSPA internal request handling.

4. **Service Integration**: Use dependency injection to pass Symfony services to your components through global variables.

5. **Security**: Leverage Symfony's security component for authentication and authorization within components.

6. **Performance**: Use phpSPA for specific dynamic sections rather than entire pages for optimal performance.

## Next Steps

Now you have a fully functional project dashboard that combines Symfony's enterprise features with phpSPA's dynamic frontend capabilities! The project list and team members stay static while the task board updates dynamically based on project selection.

You can extend this by:

-  Integrating with Doctrine ORM for real database operations
-  Adding Symfony's validation component for form handling
-  Implementing WebSocket integration for real-time updates
-  Using Symfony's event system for component communication
-  Adding Symfony's caching layer for improved performance

Happy coding with Symfony + phpSPA! 🚀
