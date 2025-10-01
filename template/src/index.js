/**
 * ===========================================================
 *          _             _____  _____
 *         | |           / ____||  __ \  /\    
 *   _ __  | |__   _ __ | (___  | |__) |/  \   
 *  | '_ \ | '_ \ | '_ \ \___ \ |  ___// /\ \  
 *  | |_) || | | || |_) |____) || |   / ____ \ 
 *  | .__/ |_| |_|| .__/|_____/ |_|  /_/    \_\
 *  | |           | |
 *  |_|           |_|
 * 
 * ===========================================================
 * 
 * phpSPA JavaScript Engine
 *
 * A lightweight JavaScript engine for PHP-powered single-page applications.
 * Handles SPA-style navigation, content replacement, and lifecycle events
 * without full page reloads. Designed to pair with the `phpSPA` PHP framework.
 *
 * Note:
 * - All scripts and logic must be attached per component using `$component->script(...)`.
 * - This library assumes server-rendered HTML responses with placeholder target IDs.
 *
 * @author Dave Conco <concodave@gmail.com>
 * @link https://github.com/dconco/phpspa-js
 * @version 1.1.10
 * @license MIT
 */

/**
 * UTF-8 safe base64 encoding function
 * Handles Unicode characters that btoa cannot process
 * 
 * @param {string} str - String to encode
 * @returns {string} Base64 encoded string
 */
function utf8ToBase64(str) {
   try {
      // First try the native btoa for performance
      return btoa(str);
   } catch (e) {
      // If btoa fails (due to non-Latin1 characters), use UTF-8 safe encoding
      try {
         // Modern replacement for unescape(encodeURIComponent(str))
         const utf8Bytes = new TextEncoder().encode(str);
         const binaryString = Array.from(utf8Bytes, byte => String.fromCharCode(byte)).join('');
         return btoa(binaryString);
      } catch (fallbackError) {
         // Final fallback: encode each character individually
         return btoa(
            str.split('').map(function(c) {
               return String.fromCharCode(c.charCodeAt(0) & 0xff);
            }).join('')
         );
      }
   }
}

/**
 * UTF-8 safe base64 decoding function
 * Handles Unicode characters that atob cannot process
 * 
 * @param {string} str - Base64 encoded string to decode  
 * @returns {string} Decoded string
 */
function base64ToUtf8(str) {
   try {
      // Try modern UTF-8 safe decoding first
      const binaryString = atob(str);
      const bytes = new Uint8Array(binaryString.length);
      for (let i = 0; i < binaryString.length; i++) {
         bytes[i] = binaryString.charCodeAt(i);
      }
      return new TextDecoder().decode(bytes);
   } catch (e) {
      // Fallback to regular atob
      return atob(str);
   }
}
(function () {
   /**
    * Initialize phpSPA when DOM is ready
    * Sets up the initial browser history state with the current page content
    */
   window.addEventListener("DOMContentLoaded", () => {
      const targetElement = document.querySelector("[data-phpspa-target]");

      RuntimeManager.emit('load', {
         route: location.href,
         success: true,
         error: false
      });

      if (targetElement) {
         // Create initial state object with current page data
         const initialState = {
            url: location.href,
            title: document.title,
            targetID: targetElement.id,
            content: targetElement.innerHTML,
            root: true,
         };

         // Check if component has auto-reload functionality
         if (targetElement.hasAttribute("phpspa-reload-time")) {
            initialState.reloadTime = Number(
               targetElement.getAttribute("phpspa-reload-time")
            );
         }

         // Replace current history state with phpSPA data
         RuntimeManager.replaceState(
            initialState,
            document.title,
            location.href
         );

         // Set up auto-reload if specified
         if (targetElement.hasAttribute("phpspa-reload-time")) {
            setTimeout(phpspa.reloadComponent, initialState.reloadTime);
         }
      }
   });

   /**
    * Handle clicks on phpSPA navigation links
    * Intercepts clicks on elements with data-type="phpspa-link-tag"
    * and routes them through the SPA navigation system
    */
   document.addEventListener("click", (event) => {
      const spaLink = event.target.closest('a[data-type="phpspa-link-tag"]');

      if (spaLink) {
         // Prevent default browser navigation
         event.preventDefault();

         // Navigate using phpSPA system
         phpspa.navigate(new URL(spaLink.href, location.href), "push");
      }
   });

   /**
    * Handle browser back/forward button navigation
    * Restores page content when user navigates through browser history
    */
   window.addEventListener("popstate", (event) => {
      event.preventDefault();
      const navigationState = event.state;

      RuntimeManager.emit('beforeload', { route: location.href });

      // Enable automatic scroll restoration
      history.scrollRestoration = "auto";

      // Check if we have valid phpSPA state data
      if (navigationState && navigationState.content) {
         // Restore page title
         document.title = navigationState.title ?? document.title;

         // Find target container or fallback to body
         const targetContainer =
            document.getElementById(navigationState.targetID) ?? document.body;

         // Decode and restore HTML content
         targetContainer.innerHTML = navigationState.content;

         // Clear old executed scripts cache
         RuntimeManager.clearExecutedScripts();

         // Execute any inline scripts and styles in the restored content
         RuntimeManager.runAll(navigationState.root ? document.body : targetContainer);

         // Restart auto-reload timer if needed
         if (typeof navigationState.reloadTime !== "undefined") {
            setTimeout(phpspa.reloadComponent, navigationState.reloadTime);
         }

         RuntimeManager.emit('load', {
            route: location.href,
            success: true,
            error: false
         });
      } else {
         // No valid state found - navigate to current URL to refresh
         phpspa.navigate(new URL(location.href), "replace");
      }
   });
})();

/**
 * A static class for managing client-side navigation and state in a PHP-powered Single Page Application (SPA).
 * Provides methods for navigation, history manipulation, event handling, and dynamic content updates.
 *
 * @class phpspa
 *
 * @method navigate
 * @static
 * @param {string|URL} url - The URL to navigate to.
 * @param {string} [state="push"] - The history state action ("push" or "replace").
 * @description Fetches content from the given URL using a custom method, updates the DOM, manages history state, and executes inline scripts.
 *
 * @method back
 * @static
 * @description Navigates back in the browser history.
 *
 * @method forward
 * @static
 * @description Navigates forward in the browser history.
 *
 * @method reload
 * @static
 * @description Reloads the current page content via SPA navigation.
 *
 * @method on
 * @static
 * @param {string} event - The event name to listen for.
 * @param {Function} callback - The callback to execute when the event is emitted.
 * @description Registers an event listener for a custom event.
 *
 */
class phpspa {
   /**
    * Navigates to a given URL using PHPSPA's custom navigation logic.
    * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
    * emits lifecycle events, and executes inline scripts.
    *
    * @param {string|URL} url - The URL or path to navigate to.
    * @param {"push"|"replace"} [state="push"] - Determines whether to push or replace the browser history state.
    *
    * @fires phpspa#beforeload - Emitted before loading the new route.
    * @fires phpspa#load - Emitted after attempting to load the new route, with success or error status.
    */
   static navigate(url, state = "push") {
      // Emit beforeload event for loading indicators
      RuntimeManager.emit("beforeload", { route: url });

      // Fetch content from the server with phpSPA headers
      fetch(url, {
         headers: {
            "X-Requested-With": "PHPSPA_REQUEST",
         },
         mode: "same-origin",
         redirect: "follow",
         keepalive: true,
      })
         .then((response) => {
            response
               .text()
               .then((responseText) => {
                  let responseData;

                  // Try to parse JSON response, fallback to raw text
                  if (responseText && responseText.trim().startsWith("{")) {
                     try {
                        responseData = JSON.parse(responseText);
                     } catch (parseError) {
                        responseData = responseText;
                     }
                  } else {
                     responseData = responseText || ""; // Handle empty responses
                  }

                  processResponse(responseData);

                  // Emit successful load event
                  RuntimeManager.emit("load", {
                     route: url,
                     success: true,
                     error: false,
                  });
               })
               .catch((error) => handleError(error));
         })
         .catch((error) => handleError(error));

      /**
       * Handles errors that occur during navigation requests
       * @param {Error} error - The error object from the failed request
       */
      function handleError(error) {
         // Check if the error has a response body (HTTP 4xx/5xx errors)
         if (error.response) {
            error.response
               .text()
               .then((fallbackResponse) => {
                  let errorData;

                  try {
                     // Attempt to parse error response as JSON
                     errorData = fallbackResponse.trim().startsWith("{")
                        ? JSON.parse(fallbackResponse)
                        : fallbackResponse;
                  } catch (parseError) {
                     // If parsing fails, use raw text
                     errorData = fallbackResponse;
                  }

                  processResponse(errorData || "");

                  RuntimeManager.emit("load", {
                     route: url,
                     success: false,
                     error: error.message || "Server returned an error",
                     data: errorData,
                  });
               })
               .catch(() => {
                  processResponse("");

                  // Failed to read error response body
                  RuntimeManager.emit("load", {
                     route: url,
                     success: false,
                     error: error.message || "Failed to read error response",
                  });
               });
         } else {
            processResponse("");

            // Network error, same-origin issue, or other connection problems
            RuntimeManager.emit("load", {
               route: url,
               success: false,
               error: error.message || "No connection to server",
            });
         }
      }

      /**
       * Processes the server response and updates the DOM
       * @param {string|Object} responseData - The processed response data
       */
      function processResponse(responseData) {
         // Update document title if provided
         if (
            typeof responseData?.title === "string" ||
            typeof responseData?.title === "number"
         ) {
            document.title = responseData.title;
         }

         // Find target element for content replacement
         const targetElement =
            document.getElementById(responseData?.targetID) ??
            document.getElementById(history.state?.targetID) ??
            document.body;

         // Update content - decode base64 if provided, otherwise use raw data
         targetElement.innerHTML = responseData?.content
            ? responseData.content
            : responseData;

         // Prepare state data for browser history
         const stateData = {
            url: url?.href ?? url,
            title: responseData?.title ?? document.title,
            targetID: responseData?.targetID ?? targetElement.id,
            content: responseData?.content ?? responseData,
         };

         // Include reload time if specified
         if (typeof responseData.reloadTime !== "undefined") {
            stateData.reloadTime = responseData.reloadTime;
         }

         // Update browser history
         if (state === "push") {
            RuntimeManager.pushState(stateData, stateData.title, url);
         } else if (state === "replace") {
            RuntimeManager.replaceState(stateData, stateData.title, url);
         }

         // Handle URL fragments (hash navigation)
         const hashElement = document.getElementById(url?.hash?.substring(1));

         if (hashElement) {
            scroll({
               top: hashElement.offsetTop,
               left: hashElement.offsetLeft,
            });
         } else {
            scroll(0, 0); // Scroll to top if no hash or element not found
         }

         // Clear old executed scripts cache
         RuntimeManager.clearExecutedScripts();

         // Execute any inline scripts and styles in the new content
         RuntimeManager.runAll(targetElement);

         // Set up auto-reload if specified
         if (typeof responseData.reloadTime !== "undefined") {
            setTimeout(phpspa.reloadComponent, responseData.reloadTime);
         }
      }
   }

   /**
    * Navigates back in the browser history.
    * Uses the native browser history API.
    */
   static back() {
      history.back();
   }

   /**
    * Navigates forward in the browser's session history.
    * Uses the native browser history API.
    */
   static forward() {
      history.forward();
   }

   /**
    * Reloads the current page by navigating to the current URL using the "replace" history mode.
    * This does not add a new entry to the browser's history stack.
    */
   static reload() {
      phpspa.navigate(new URL(location.href), "replace");
   }

   /**
    * Registers a callback function to be executed when the specified event is triggered.
    *
    * @param {string} event - The name of the event to listen for.
    * @param {Function} callback - The function to call when the event is triggered.
    */
   static on(event, callback) {
      if (!RuntimeManager.events[event]) {
         RuntimeManager.events[event] = [];
      }
      RuntimeManager.events[event].push(callback);
   }

   /**
    * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
    * Preserves the current scroll position during the update.
    *
    * @param {string} key - The key representing the state to update.
    * @param {*} value - The new value to set for the specified state key.
    * @returns {Promise<void>} A promise that resolves when the state is updated successfully.
    *
    * @example
    * phpspa.setState('user', { name: 'Alice' })
    *   .then(() => console.log('State updated!'))
    *   .catch(err => console.error('Failed to update state:', err));
    */
   static setState(key, value) {
      return new Promise((resolve, reject) => {
         // Preserve current scroll position
         const currentScroll = {
            top: scrollY,
            left: scrollX,
         };

         const currentUrl = new URL(location.href);
         const statePayload = JSON.stringify({ state: { key, value } });

         // Send state update request with authorization header
         fetch(currentUrl, {
            headers: {
               "X-Requested-With": "PHPSPA_REQUEST",
               Authorization: `Bearer ${utf8ToBase64(statePayload)}`,
            },
            mode: "same-origin",
            redirect: "follow",
            keepalive: true,
         })
            .then((response) => {
               response
                  .text()
                  .then((responseText) => {
                     let responseData;

                     // Parse response as JSON if possible
                     if (responseText && responseText.trim().startsWith("{")) {
                        try {
                           responseData = JSON.parse(responseText);
                        } catch (parseError) {
                           responseData = responseText;
                        }
                     } else {
                        responseData = responseText || "";
                     }

                     resolve();
                     updateContent(responseData);
                  })
                  .catch((error) => {
                     reject(error.message);
                     handleStateError(error);
                  });
            })
            .catch((error) => {
               reject(error.message);
               handleStateError(error);
            });

         /**
          * Handles errors during state update requests
          * @param {Error} error - The error that occurred
          */
         function handleStateError(error) {
            if (error.response) {
               error.response
                  .text()
                  .then((fallbackResponse) => {
                     let errorData;

                     try {
                        errorData = fallbackResponse.trim().startsWith("{")
                           ? JSON.parse(fallbackResponse)
                           : fallbackResponse;
                     } catch (parseError) {
                        errorData = fallbackResponse;
                     }

                     updateContent(errorData || "");
                  })
                  .catch(() => {
                     updateContent("");
                  });
            } else {
               updateContent("");
            }
         }

         /**
          * Updates the DOM content and restores scroll position
          * @param {string|Object} responseData - The response data to process
          */
         function updateContent(responseData) {
            // Update title if provided
            if (
               typeof responseData?.title === "string" ||
               typeof responseData?.title === "number"
            ) {
               document.title = responseData.title;
            }

            // Find target element and update content
            const targetElement =
               document.getElementById(responseData?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            targetElement.innerHTML = responseData?.content
               ? responseData.content
               : responseData;

            // Clear old executed scripts cache
            RuntimeManager.clearExecutedScripts();

            // Execute scripts and styles, then restore scroll position
            RuntimeManager.runAll(targetElement);
            scroll(currentScroll);
         }
      });
   }

   /**
    * Reloads the current component content while preserving scroll position.
    * Useful for refreshing dynamic content without full page navigation.
    */
   static reloadComponent() {
      // Save current scroll position
      const currentScroll = {
         top: scrollY,
         left: scrollX,
      };

      // Fetch current page content
      fetch(new URL(location.href), {
         headers: {
            "X-Requested-With": "PHPSPA_REQUEST",
         },
         mode: "same-origin",
         redirect: "follow",
         keepalive: true,
      })
         .then((response) => {
            response
               .text()
               .then((responseText) => {
                  let responseData;

                  // Parse response
                  if (responseText && responseText.trim().startsWith("{")) {
                     try {
                        responseData = JSON.parse(responseText);
                     } catch (parseError) {
                        responseData = responseText;
                     }
                  } else {
                     responseData = responseText || "";
                  }

                  updateComponentContent(responseData);
               })
               .catch((error) => {
                  handleComponentError(error);
               });
         })
         .catch((error) => {
            handleComponentError(error);
         });

      /**
       * Handles errors during component reload
       * @param {Error} error - The error that occurred
       */
      function handleComponentError(error) {
         if (error.response) {
            error.response
               .text()
               .then((fallbackResponse) => {
                  let errorData;

                  try {
                     errorData = fallbackResponse.trim().startsWith("{")
                        ? JSON.parse(fallbackResponse)
                        : fallbackResponse;
                  } catch (parseError) {
                     errorData = fallbackResponse;
                  }

                  updateComponentContent(errorData || "");
               })
               .catch(() => {
                  updateComponentContent("");
               });
         } else {
            updateComponentContent("");
         }
      }

      /**
       * Updates the component content and handles auto-reload
       * @param {string|Object} responseData - The response data
       */
      function updateComponentContent(responseData) {
         // Update title if provided
         if (
            typeof responseData?.title === "string" ||
            typeof responseData?.title === "number"
         ) {
            document.title = responseData.title;
         }

         // Find target and update content
         const targetElement =
            document.getElementById(responseData?.targetID) ??
            document.getElementById(history.state?.targetID) ??
            document.body;

         targetElement.innerHTML = responseData?.content
            ? responseData.content
            : responseData;

         // Clear old executed scripts cache
         RuntimeManager.clearExecutedScripts();

         // Execute scripts and restore scroll
         RuntimeManager.runAll(targetElement);
         scroll(currentScroll);

         // Set up next auto-reload if specified
         if (typeof responseData.reloadTime !== "undefined") {
            setTimeout(phpspa.reloadComponent, responseData.reloadTime);
         }
      }
   }

   /**
    * Makes an authenticated call to the server with a token and arguments.
    * Used for server-side function calls from the client.
    *
    * @param {string} token - The authentication token for the call
    * @param {...any} args - Arguments to pass to the server function
    * @returns {Promise<string>} The decoded response from the server
    */
   static async __call(token, ...args) {
      const currentUrl = new URL(location.href);
      const callPayload = JSON.stringify({ __call: { token, args } });

      try {
         const response = await fetch(currentUrl, {
            headers: {
               "X-Requested-With": "PHPSPA_REQUEST",
               Authorization: `Bearer ${utf8ToBase64(callPayload)}`,
            },
            mode: "same-origin",
            redirect: "follow",
            keepalive: true,
         });

         const responseText = await response.text();
         let responseData;

         // Parse and decode response
         if (responseText && responseText.trim().startsWith("{")) {
            try {
               responseData = JSON.parse(responseText);
               responseData = responseData?.response
                  ? JSON.parse(responseData.response)
                  : responseData;
            } catch (parseError) {
               responseData = responseText;
            }
         } else {
            responseData = responseText || "";
         }

         return responseData;
      } catch (error) {
         // Handle errors with response bodies
         if (error.response) {
            try {
               const fallbackResponse = await error.response.text();
               let errorData;

               try {
                  errorData = fallbackResponse.trim().startsWith("{")
                     ? JSON.parse(fallbackResponse)
                     : fallbackResponse;

                  errorData = errorData?.response
                     ? JSON.parse(errorData.response)
                     : errorData;
               } catch (parseError) {
                  errorData = fallbackResponse;
               }

               return errorData;
            } catch {
               return "";
            }
         } else {
            // Network errors or other issues
            return "";
         }
      }
   }
}

/**
 * Runtime Manager for phpSPA
 *
 * Handles script execution, style injection, event management, and browser history
 * for the phpSPA framework. Uses an obscure class name to avoid conflicts.
 *
 * @class RuntimeManager
 */
class RuntimeManager {
   /**
    * Tracks executed scripts to prevent duplicates
    * @type {Set<string>}
    * @private
    */
   static executedScripts = new Set();

   /**
    * Tracks executed styles to prevent duplicates
    * @type {Set<string>}
    * @private
    */
   static executedStyles = new Set();

   /**
    * A static cache object that stores processed script content to avoid redundant processing.
    * Used to improve performance by caching scripts that have already been processed or compiled.
    *
    * @static
    * @type {Object<string, string>}
    * @memberof RuntimeManager
    */
   static ScriptsCachedContent = {};

   /**
    * Internal event registry for custom events
    * @type {Object<string, Function[]>}
    * @private
    */
   static events = {
      beforeload: [],
      load: [],
   };

   /**
    * Executes inline scripts and styles within a container element
    * Processes all script and style tags, preventing duplicate execution
    *
    * @param {HTMLElement} container - The container element to search for scripts and styles
    */
   static runAll(container) {
      this.runInlineScripts(container);
      this.runInlineStyles(container);
      this.runPhpSpaScripts(container);
   }

   /**
    * Processes and executes inline scripts within a container
    * Creates isolated scopes using IIFE to prevent variable conflicts
    *
    * @param {HTMLElement} container - The container to search for script elements
    * @private
    */
   static runInlineScripts(container) {
      const scripts = container.querySelectorAll("script");

      scripts.forEach((script) => {
         // Use base64 encoded content as unique identifier
         const contentHash = utf8ToBase64(script.textContent.trim());

         // Skip if this script has already been executed
         if (!this.executedScripts.has(contentHash)) {
            this.executedScripts.add(contentHash);

            // Create new script element
            const newScript = document.createElement("script");

            // Copy all attributes except the data-type identifier
            for (const attribute of script.attributes) {
               newScript.setAttribute(attribute.name, attribute.value);
            }

            // Check if script should run in async context
            const isAsync = script.hasAttribute("async");

            // Wrap in IIFE to create isolated scope
            if (isAsync) {
               newScript.textContent = `(async function() {\n${script.textContent}\n})();`;
            } else {
               newScript.textContent = `(function() {\n${script.textContent}\n})();`;
            }

            // Execute and immediately remove from DOM
            document.head.appendChild(newScript).remove();
         }
      });
   }


   static runPhpSpaScripts(container) {
      const scripts = container.querySelectorAll("phpspa-script, script[data-type=\"phpspa/script\"]");

      scripts.forEach(async (script) => {
         const scriptUrl = script.getAttribute('src');
         const nonce = document.documentElement.getAttribute('x-phpspa');

         // Skip if this script has already been executed
         if (!this.executedScripts.has(scriptUrl)) {
            this.executedScripts.add(scriptUrl);

            // Check cache first
            if (this.ScriptsCachedContent[scriptUrl]) {
               const newScript = document.createElement("script");
               newScript.textContent = this.ScriptsCachedContent[scriptUrl];
               newScript.type = 'text/javascript';
               newScript.nonce = nonce;
               
               // Execute and immediately remove from DOM
               document.head.appendChild(newScript).remove();
               return;
            }

            const response = await fetch(scriptUrl, {
               mode: "same-origin",
               headers: {
                  "X-Requested-With": "PHPSPA_REQUEST_SCRIPT",
               },
            });
            
            if (response.ok) {
               const scriptContent = await response.text();
               
               // Create new script element
               const newScript = document.createElement("script");
               newScript.textContent = scriptContent;
               newScript.type = 'text/javascript';
               newScript.nonce = nonce;

               // Execute and immediately remove from DOM
               document.head.appendChild(newScript).remove();
   
               // Cache the fetched script content
               this.ScriptsCachedContent[scriptUrl] = scriptContent;
            } else {
               console.error(`Failed to load script from ${scriptUrl}: ${response.statusText}`);
            }
         }
      });
   }


   /**
    * Clears all executed scripts from the runtime manager.
    * This method removes all entries from the executedScripts collection,
    * effectively resetting the tracking of previously executed scripts.
    *
    * @static
    * @memberof RuntimeManager
    * @since 1.0.0
    */
   static clearExecutedScripts() {
      RuntimeManager.executedScripts.clear();
   }

   /**
    * Processes and injects inline styles within a container
    * Prevents duplicate style injection by tracking content hashes
    *
    * @param {HTMLElement} container - The container to search for style elements
    * @private
    */
   static runInlineStyles(container) {
      const styles = container.querySelectorAll("style");

      styles.forEach((style) => {
         // Use base64 encoded content as unique identifier
         const contentHash = utf8ToBase64(style.textContent.trim());

         // Skip if this style has already been injected
         if (!this.executedStyles.has(contentHash)) {
            this.executedStyles.add(contentHash);

            // Create new style element
            const newStyle = document.createElement("style");

            // Copy all attributes except the data-type identifier
            for (const attribute of style.attributes) {
               newStyle.setAttribute(attribute.name, attribute.value);
            }

            // Copy style content and inject into head
            newStyle.textContent = style.textContent;
            document.head.appendChild(newStyle).remove();
         }
      });
   }

   /**
    * Emits a custom event to all registered listeners
    * Used for lifecycle events like 'beforeload' and 'load'
    *
    * @param {string} eventName - The name of the event to emit
    * @param {Object} payload - The data to pass to event listeners
    */
   static emit(eventName, payload) {
      const callbacks = this.events[eventName] || [];

      // Execute all registered callbacks for this event
      for (const callback of callbacks) {
         if (typeof callback === "function") {
            try {
               callback(payload);
            } catch (error) {
               // Log callback errors but don't break the chain
               console.error(`Error in ${eventName} event callback:`, error);
            }
         }
      }
   }

   /**
    * Safely pushes a new state to browser history
    * Wraps in try-catch to handle potential browser restrictions
    *
    * @param {...any} stateArgs - Arguments to pass to history.pushState
    */
   static pushState(...stateArgs) {
      try {
         history.pushState(...stateArgs);
      } catch (error) {
         // Silently handle history API restrictions
         console.warn("Failed to push history state:", error.message);
      }
   }

   /**
    * Safely replaces current browser history state
    * Wraps in try-catch to handle potential browser restrictions
    *
    * @param {...any} stateArgs - Arguments to pass to history.replaceState
    */
   static replaceState(...stateArgs) {
      try {
         history.replaceState(...stateArgs);
      } catch (error) {
         // Silently handle history API restrictions
         console.warn("Failed to replace history state:", error.message);
      }
   }
}

/**
 * Global helper function for updating application state
 * Provides a convenient shorthand for phpspa.setState()
 *
 * @param {string} key - The state key to update
 * @param {any} value - The new value for the state key
 * @returns {Promise<void>} Promise that resolves when state is updated
 */
if (typeof setState !== "function") {
   function setState(key, value) {
      return phpspa.setState(key, value);
   }
}

/**
 * Global helper function for making server calls
 * Provides a convenient shorthand for phpspa.__call()
 *
 * @param {string} token - The function token/identifier
 * @param {...any} args - Arguments to pass to the server function
 * @returns {Promise<string>} Promise that resolves with the server response
 */
if (typeof __call !== "function") {
   function __call(token, ...args) {
      return phpspa.__call(token, ...args);
   }
}

/**
 * Initialize phpSPA global object
 * Makes the phpspa class available globally for easy access
 */
(function () {
   if (typeof window.phpspa === "undefined") {
      window.phpspa = phpspa;
   }
})();
