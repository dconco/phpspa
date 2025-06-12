# ⏳ Loading Event Hooks

Sometimes when a route is loading — especially over AJAX — you don't want your users staring at a blank page. That's where **loading states** come in.

In `phpSPA`, loading states are handled using **event hooks** you can register globally or per-component. These give you full control over UI behaviors during navigation.

---

## 🧮 Global Loading via Events

Hook into the `beforeload` and `load` lifecycle events to create seamless loading experiences:

=== "Basic Setup"

    ```js
    phpspa.on("beforeload", ({ route }) => {
        // Show a global spinner // (1)
        console.log(`Loading ${route}...`);
    });

    phpspa.on("load", ({ route, success, error }) => {
        // Hide spinner and handle result // (2)
        console.log(`Finished loading ${route}`);
    });
    ```

    1. This fires immediately when navigation starts
    2. This fires when the component has finished loading (success or failure)

=== "With Error Handling"

    ```js
    phpspa.on("beforeload", ({ route }) => {
        showGlobalSpinner();
        logNavigation(route);
    });

    phpspa.on("load", ({ route, success, error }) => {
        hideGlobalSpinner();
        
        if (!success) {
            handleLoadingError(route, error);
        } else {
            trackSuccessfulNavigation(route);
        }
    });
    ```

!!! info "Event Lifecycle"
    The loading events follow a predictable pattern:

    1. **User clicks link** → Navigation starts
    2. **`beforeload` fires** → Perfect time to show loading UI
    3. **phpSPA fetches component** → Network request happens
    4. **`load` fires** → Component loaded (or failed), update UI accordingly

---

## 📌 Event Parameters Reference

Each event provides rich context about the current navigation:

| Parameter | Type           | Description                           | Available In         |
| --------- | -------------- | ------------------------------------- | -------------------- |
| `route`   | `string`       | The path being navigated to           | `beforeload`, `load` |
| `success` | `boolean`      | Whether component loaded successfully | `load` only          |
| `error`   | `object\|null` | Error details if loading failed       | `load` only          |

### 🔍 Parameter Details

=== "Route Parameter"

    ```js
    phpspa.on("beforeload", ({ route }) => {
        console.log(route); // "/users/123", "/dashboard", etc.
        
        // Route-specific loading
        if (route.startsWith('/admin')) {
            showAdminLoader();
        } else {
            showStandardLoader();
        }
    });
    ```

=== "Success Parameter"

    ```js
    phpspa.on("load", ({ success, route }) => {
        if (success) {
            // Component loaded successfully
            analytics.track('page_view', { route });
        } else {
            // Something went wrong
            analytics.track('page_error', { route });
        }
    });
    ```

=== "Error Parameter"

    ```js
    phpspa.on("load", ({ error, route }) => {
        if (error) {
            console.error('Loading failed:', error);
            
            // Show user-friendly error based on type
            if (error.status === 404) {
                showNotFoundMessage();
            } else if (error.status >= 500) {
                showServerErrorMessage();
            }
        }
    });
    ```

---

## 🎨 Complete Loading Implementation

Here's a production-ready loading system with multiple spinner styles:

=== "CSS Spinner"

    ```html
    <script>
        phpspa.on("beforeload", ({ route }) => {
            const loader = document.createElement("div");
            loader.className = "loader";
            loader.id = "global-loader";
            document.body.appendChild(loader); // (1)
            console.log("Navigating to:", route);
        });

        phpspa.on("load", ({ route, success, error }) => {
            document.getElementById("global-loader")?.remove(); // (2)

            if (!success) {
                console.error("Failed to load:", route);
                showErrorNotification("Something went wrong loading this page.");
            }
        });
    </script>

    <style>
    .loader {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 30px;
        height: 30px;
        border: 4px solid #ccc;
        border-top-color: #007bff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        z-index: 9999;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
    ```

    1. Dynamically creates and shows the spinner
    2. Removes the spinner when loading completes

=== "Progress Bar"

    ```html
    <script>
        phpspa.on("beforeload", ({ route }) => {
            const progress = document.getElementById("loading-bar");
            progress.style.width = "0%";
            progress.style.display = "block";
            progress.style.width = "30%"; // (1)
        });

        phpspa.on("load", ({ success }) => {
            const progress = document.getElementById("loading-bar");
            progress.style.width = "100%"; // (2)
            
            setTimeout(() => {
                progress.style.display = "none";
            }, 200);
        });
    </script>

    <div id="loading-bar" style="
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        background: linear-gradient(90deg, #007bff, #28a745);
        transition: width 0.3s ease;
        z-index: 9999;
        display: none;
    "></div>
    ```

    1. Start the progress bar at 30% when loading begins
    2. Complete to 100% when loading finishes

=== "Skeleton Loading"

    ```html
    <script>
        phpspa.on("beforeload", ({ route }) => {
            const skeleton = createSkeletonForRoute(route);
            document.getElementById("content").innerHTML = skeleton;
        });

        phpspa.on("load", ({ success }) => {
            if (success) {
                // Content will be replaced by the actual component
                console.log("Component loaded successfully");
            }
        });

        function createSkeletonForRoute(route) {
            if (route.includes('profile')) {
                return `
                    <div class="skeleton-profile">
                        <div class="skeleton-avatar"></div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text short"></div>
                    </div>
                `;
            }
            return '<div class="skeleton-generic">Loading...</div>';
        }
    </script>
    ```

!!! tip "Loading UX Best Practices"
    - 🎯 **Show loading immediately** - Don't wait for network delays
    - 🔄 **Match loading to content** - Different spinners for different page types
    - ⚡ **Keep it lightweight** - Loading UI should appear instantly
    - 🛡️ **Handle errors gracefully** - Always provide fallback messaging
    - 📱 **Consider mobile** - Ensure loading states work on touch devices

---

## 🧩 Per-Component Loading

You can define component-specific loading behavior using `<script type="phpspa/script">`:

=== "Component-Specific Loading"

    ```html
    <!-- Inside your component -->
    <script type="phpspa/script">
        phpspa.on("beforeload", ({ route }) => {
            // This only runs when THIS component is being loaded // (1)
            showComponentSpecificLoader();
        });

        phpspa.on("load", ({ success, error }) => {
            hideComponentSpecificLoader();
            
            if (!success) {
                alert("Failed to load this view.");
            }
        });
    </script>
    ```

    1. These event handlers are scoped to this component only

=== "Dashboard Component Example"

    ```html
    <!-- DashboardComponent.php -->
    <div class="dashboard">
        <h1>Dashboard</h1>
        <div id="dashboard-content">
            <!-- Content will load here -->
        </div>
    </div>

    <script type="phpspa/script">
        phpspa.on("beforeload", ({ route }) => {
            // Show dashboard-specific loading
            document.getElementById("dashboard-content").innerHTML = `
                <div class="dashboard-skeleton">
                    <div class="card-skeleton"></div>
                    <div class="chart-skeleton"></div>
                    <div class="table-skeleton"></div>
                </div>
            `;
        });

        phpspa.on("load", ({ success }) => {
            if (success) {
                // Initialize dashboard widgets
                initDashboardCharts();
                loadRecentActivity();
            }
        });
    </script>
    ```

!!! warning "Component Scope"
    Per-component loading events are **scoped to that component**. They only fire when that specific component is being loaded, not during other navigations.

---

## 🔄 Loading Trigger Conditions

Understanding when loading events fire helps you build better user experiences:

!!! info "Loading Event Triggers"

    **✅ Loading Events Fire For:**
    :   - phpSPA route navigations
    :   - Programmatic navigation via `phpspa.navigate()`
    
    **❌ Loading Events Don't Fire For:**
    :   - Browser back/forward with phpSPA routes
    :   - Initial page load (server-rendered)
    :   - External link clicks
    :   - Page refreshes
    :   - Non-phpSPA navigation

=== "Navigation Types"

    ```js
    // ✅ These trigger loading events
    phpspa.navigate('/dashboard');
    <a href="/users" data-type="phpspa-link-tag">Users</a>

    // ❌ These don't trigger loading events
    // Browser back/forward buttons
    window.location.href = '/external-site';
    <a href="/users">Users</a> (without data-phpspa)
    window.location.reload();
    ```

=== "Conditional Loading"

    ```js
    phpspa.on("beforeload", ({ route }) => {
        // Only show loading for slow routes
        const slowRoutes = ['/reports', '/analytics', '/export'];
        
        if (slowRoutes.some(r => route.startsWith(r))) {
            showHeavyLoadingSpinner();
        } else {
            showLightLoadingIndicator();
        }
    });
    ```

---

## 💡 Advanced Loading Patterns

=== "Debounced Loading"

    ```js
    let loadingTimeout;

    phpspa.on("beforeload", ({ route }) => {
        // Only show loading if it takes more than 200ms
        loadingTimeout = setTimeout(() => {
            showSpinner();
        }, 200);
    });

    phpspa.on("load", ({ success }) => {
        clearTimeout(loadingTimeout);
        hideSpinner();
    });
    ```

=== "Loading with Analytics"

    ```js
    let loadingStartTime;

    phpspa.on("beforeload", ({ route }) => {
        loadingStartTime = Date.now();
        showSpinner();
    });

    phpspa.on("load", ({ route, success, error }) => {
        const loadTime = Date.now() - loadingStartTime;
        
        hideSpinner();
        
        // Track loading performance
        analytics.track('page_load', {
            route,
            success,
            loadTime,
            error: error?.message
        });
    });
    ```

=== "Retry on Failure"

    ```js
    phpspa.on("load", ({ route, success, error }) => {
        if (!success && error.status >= 500) {
            // Auto-retry server errors
            setTimeout(() => {
                phpspa.navigate(route);
            }, 2000);
            
            showRetryMessage("Retrying in 2 seconds...");
        }
    });
    ```

---

!!! success "Loading States Improve UX"
    You're not required to define loading states, but they **dramatically improve user experience** — especially on:

    - 🐌 **Slow networks** - Users see immediate feedback
    - 📱 **Mobile devices** - Reduces perceived loading time
    - 🏢 **Large applications** - Provides visual continuity
    - 🔄 **Complex components** - Shows progress during heavy operations

---

<div class="phpspa-nav">
    <div class="nav-section">
        <div class="nav-previous">
            <a href="/5-route-patterns-and-param-types/" class="nav-link">
                <span class="nav-direction">← Previous</span>
                <span class="nav-title">Route Patterns & Param Types</span>
                <span class="nav-description">Learn about pattern matching and typed parameters</span>
            </a>
        </div>

        <div class="nav-next">
            <a href="/7-layout-and-content-swap-mechanism/" class="nav-link featured">
                <span class="nav-direction">Next →</span>
                <span class="nav-title">Layout & Content Swap</span>
                <span class="nav-description">Master the layout system and content transitions</span>
                <span class="nav-badge">Essential</span>
            </a>
        </div>
    </div>
    
    <div class="nav-progress">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 60%;"></div>
        </div>
        <span class="progress-text">Chapter 6 of 10 Complete</span>
    </div>
</div>

<style>
.phpspa-nav {
    margin: 3rem 0 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.nav-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.nav-link {
    display: block;
    padding: 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-previous .nav-link {
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
    border: 1px solid #cbd5e1;
    color: #475569;
}

.nav-previous .nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #94a3b8;
}

.nav-next .nav-link {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: 1px solid #2563eb;
    color: white;
    position: relative;
}

.nav-next .nav-link.featured::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

.nav-next .nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
}

.nav-direction {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.nav-title {
    display: block;
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.nav-description {
    display: block;
    font-size: 0.875rem;
    opacity: 0.7;
    line-height: 1.4;
}

.nav-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.nav-progress {
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-section {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .phpspa-nav {
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .nav-link {
        padding: 1.25rem;
    }

    .nav-badge {
        position: static;
        display: inline-block;
        margin-top: 0.5rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .phpspa-nav {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-color: #334155;
    }

    .nav-previous .nav-link {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        border-color: #475569;
        color: #e2e8f0;
    }

    .nav-previous .nav-link:hover {
        border-color: #64748b;
    }

    .progress-bar {
        background: #334155;
    }

    .progress-text {
        color: #94a3b8;
    }
}
</style>
