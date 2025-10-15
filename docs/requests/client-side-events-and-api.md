# Client-Side Events & API

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

PhpSPA provides a powerful client-side API to hook into the navigation lifecycle and control the application's behavior directly from your JavaScript. This is essential for creating features like loading indicators, animations, and handling navigation errors.

!!! info "Event System"
    Hook into navigation events to create loading indicators, animations, and error handling.

## Listening to Navigation Events

You can listen to navigation events using `phpspa.on()`. This is perfect for showing or hiding a loading spinner.

=== "beforeload"

    This event fires **before** a new page request is made. It receives the destination `route` as an argument.

    ```javascript
    phpspa.on('beforeload', ({ route }) => {
       console.log(`Navigating to: ${route}`);
       // Show your loading spinner here
       document.getElementById('loader').style.display = 'block';
    });
    ```

=== "load"

    This event fires **after** a page request completes, whether it was successful or not.
    
    - **On Success:** `{ route, success: true, error: false }`
    - **On Failure:** `{ route, success: false, error: 'Error message', data?: ... }`

    ```javascript
    phpspa.on('load', ({ route, success, error, data }) => {
       // Hide your loading spinner here
       document.getElementById('loader').style.display = 'none';

       if (!success) {
          console.error(`Failed to load route: ${route}`, error);
          // You could show an error message to the user here
       }
    });
    ```

!!! tip "Loading States"
    Combine `beforeload` and `load` events to create smooth loading transitions.

## Client-Side API Functions

The PhpSPA object also provides several utility functions to control your application.

<div class="grid cards" markdown>

-   :material-arrow-left-right: **Navigation Control**

    ---

    `phpspa.back()` & `phpspa.forward()`
    
    Navigate backward or forward in the browser's session history.

-   :material-reload: **Page Reload**

    ---

    `phpspa.reload()`
    
    Performs a "soft" reload of the current page by re-fetching the component's content without a full browser refresh.

-   :material-refresh: **Component Refresh**

    ---

    `phpspa.reloadComponent()`
    
    A more granular reload that only refreshes the content of the currently active component. This is useful for live data updates.

-   :material-state-machine: **State Management**

    ---

    `phpspa.setState(key, value)`
    
    The same as the global `setState()` function. It updates a state variable and returns a **promise** that resolves when the re-render is complete.

</div>

!!! example "State Promise"
!!! example "State Promise"
    ```javascript
    setState('counter', 5).then(() => {
       console.log('Counter has been updated and the component has re-rendered!');
    });
    ```
