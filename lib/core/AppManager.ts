import { ComponentObject, StateObject, StateValueType } from "../types/StateObjectTypes";
import { EventObject, EventPayload } from "../types/RuntimeInterfaces";
import { utf8ToBase64 } from "../utils/baseConverter";
import RuntimeManager from "./RuntimeManager";
import morphdom from "morphdom";

export default class AppManager {
   /**
    * Navigates to a given URL using PHPSPA's custom navigation logic.
    * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
    * emits lifecycle events, and executes inline scripts.
    *
    * @param url - The URL or path to navigate to.
    * @param state Determines whether to push or replace the browser history state.
    *
    * @fires AppManager#beforeload - Emitted before loading the new route.
    * @fires AppManager#load - Emitted after attempting to load the new route, with success or error status.
    */
   public static navigate(url: URL|string, state: 'push' | 'replace' = "push") {
      const newUrl = url instanceof URL ? url : new URL(url, location.toString());

      // --- Emit beforeload event for loading indicators ---
      RuntimeManager.emit("beforeload", { route: newUrl.toString() });

      // --- Fetch content from the server with PhpSPA headers ---
      fetch(newUrl, {
         headers: {
            "X-Requested-With": "PHPSPA_REQUEST",
            "X-Phpspa-Target": "navigate",
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

                  // --- Try to parse JSON response, fallback to raw text ---
                  if (responseText && responseText.trim().startsWith("{")) {
                     try {
                        responseData = JSON.parse(responseText);
                     } catch (parseError) {
                        responseData = responseText;
                     }
                  } else {
                     responseData = responseText || ""; // --- Handle empty responses ---
                  }

                  processResponse(responseData);
               })
               .catch((error) => handleError(error));
         })
         .catch((error) => handleError(error));

      /**
       * Handles errors that occur during navigation requests
       * @param {Error} error - The error object from the failed request
       */
      function handleError(error: any) {
         // --- Check if the error has a response body (HTTP 4xx/5xx errors) ---
         if (error.response) {
            error.response
               .text()
               .then((fallbackResponse: any) => {
                  let errorData;

                  try {
                     // --- Attempt to parse error response as JSON ---
                     errorData = fallbackResponse?.trim().startsWith("{")
                        ? JSON.parse(fallbackResponse)
                        : fallbackResponse;
                  } catch (parseError) {
                     // --- If parsing fails, use raw text ---
                     errorData = fallbackResponse;
                  }

                  processResponse(errorData || "");

                  RuntimeManager.emit("load", {
                     route: newUrl.toString(),
                     success: false,
                     error: error.message || "Server returned an error",
                     data: errorData,
                  });
               })
               .catch(() => {
                  processResponse("");

                  // --- Failed to read error response body ---
                  RuntimeManager.emit("load", {
                     route: newUrl.toString(),
                     success: false,
                     error: error.message || "Failed to read error response",
                  });
               });
         } else {
            processResponse("");

            // --- Network error, same-origin issue, or other connection problems ---
            RuntimeManager.emit("load", {
               route: newUrl.toString(),
               success: false,
               error: error.message || "No connection to server",
            });
         }
      }

      /**
       * Processes the server response and updates the DOM
       */
      function processResponse(responseData: ComponentObject|string) {
         const component: ComponentObject = typeof responseData === 'string'
            ? { content: responseData }
            : responseData;

         // --- Update document title if provided ---
         if (component?.title && component.title.length > 0) {
            document.title = component.title;
         }

         // --- Find target element for content replacement ---
         const targetElement =
            document.getElementById(component?.targetID) ??
            document.getElementById(history.state?.targetID) ??
            document.body;

         if (component?.targetID) {
            RuntimeManager.currentRoutes[component.targetID] = {
               route: newUrl,
               exact: component.exact ?? false,
               defaultContent: RuntimeManager.currentRoutes[component.targetID]?.defaultContent ?? targetElement.innerHTML
            }
         }

         const currentRoutes = RuntimeManager.currentRoutes;

         for (const targetID in currentRoutes) {
            if (!Object.hasOwn(currentRoutes, targetID)) continue;

            const targetInfo = currentRoutes[targetID];

            // --- If route is exact and the route target ID is not equal to the navigated route target ID ---
            // --- Then the document URL has changed ---
            // --- That is they are navigating away ---
            // --- And any route with exact === true must go back to its default content ---
            if (targetInfo.exact === true && targetID !== component?.targetID) {
               let currentHTML = document.getElementById(targetID)
               if (currentHTML) {
                  try {
                     morphdom(currentHTML, '<div>' + targetInfo.defaultContent + '</div>', {
                        childrenOnly: true
                     });
                  } catch {
                     currentHTML.innerHTML = targetInfo.defaultContent;
                  }
               }

               delete currentRoutes[targetID];
            }
         }

         // --- Update content ---
         const updateDOM = () => {
            try {
               morphdom(targetElement, '<div>' + component.content + '</div>', {
                  childrenOnly: true
               });
            } catch {
               targetElement.innerHTML = component.content;
            }
         }


         const stateData: StateObject = {
            url: newUrl.toString(),
            title: component?.title ?? document.title,
            targetID: targetElement.id,
            content: component.content,
            exact: currentRoutes[component?.targetID]?.exact,
            defaultContent: currentRoutes[component?.targetID]?.defaultContent,
         }

         // --- Include reload time if specified ---
         if (component?.reloadTime) {
            stateData.reloadTime = component.reloadTime;
         }

         const completedDOMUpdate = () => {

            // --- Update browser history ---
            if (state === "push") {
               RuntimeManager.pushState(stateData, stateData.title, newUrl);
            } else if (state === "replace") {
               RuntimeManager.replaceState(stateData, stateData.title, newUrl);
            }

            // --- Handle URL fragments (hash navigation) ---
            const hashElement = document.getElementById(newUrl.hash.substring(1));

            if (hashElement) {
               scroll({
                  top: hashElement.offsetTop,
                  left: hashElement.offsetLeft,
               });
            } else {
               scroll(0, 0); // --- Scroll to top if no hash or element not found ---
            }


            // --- Clear old executed scripts cache ---
            RuntimeManager.clearEffects();
            RuntimeManager.clearExecutedScripts();

            // --- Execute any inline scripts and styles in the new content ---
            RuntimeManager.runAll();

            // --- Emit successful load event ---
            RuntimeManager.emit("load", {
               route: newUrl.toString(),
               success: true,
               error: false,
            });

            // --- Set up auto-reload if specified ---
            if (component?.reloadTime) {
               setTimeout(AppManager.reloadComponent, component.reloadTime);
            }
         }

         if (document.startViewTransition) {
            document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
               RuntimeManager.emit('load', {
                  route: newUrl.toString(),
                  success: false,
                  error: reason || 'Unknown error during view transition',
               });
            });
         } else {
            updateDOM();
            completedDOMUpdate();
         }
      }
   }

   /**
    * Navigates back in the browser history.
    * Uses the native browser history API.
    */
   public static back() {
      history.back();
   }

   /**
    * Navigates forward in the browser's session history.
    * Uses the native browser history API.
    */
   public static forward() {
      history.forward();
   }

   /**
    * Reloads the current page by navigating to the current URL using the "replace" history mode.
    * This does not add a new entry to the browser's history stack.
    */
   public static reload() {
      AppManager.navigate(location.toString(), "replace");
   }

   /**
    * Registers a callback function to be executed when the specified event is triggered.
    *
    * @param event - The name of the event to listen for.
    * @param callback - The function to call when the event is triggered.
    */
   public static on(event: keyof EventObject, callback: (payload: EventPayload) => void) {
      if (!RuntimeManager.events[event]) {
         RuntimeManager.events[event] = [];
      }
      RuntimeManager.events[event].push(callback);

      const lastPayload = RuntimeManager.getLastEventPayload(event);
      if (lastPayload) {
         try {
            callback(lastPayload);
         } catch (error) {
            console.error(`Error in ${event} event callback:`, error);
         }
      }
   }

   /**
    * Registers a side effect to be executed after component updates.
    * Alias for RuntimeManager.registerEffect.
    * 
    * @param callback - The effect callback
    * @param dependencies - Array of state keys to listen for
    */
   public static useEffect(callback: () => Function|undefined, dependencies: string[]|null = null) {
      RuntimeManager.registerEffect(callback, dependencies);
   }

   /**
    * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
    * Preserves the current scroll position during the update.
    *
    * @param key - The key representing the state to update.
    * @param value - The new value to set for the specified state key.
    * @returns A promise that resolves when the state is updated successfully.
    *
    * @example
    * AppManager.setState('user', { name: 'Alice' })
    *   .then(() => console.log('State updated!'))
    *   .catch(err => console.error('Failed to update state:', err));
    */
   public static setState(key: string, value: StateValueType): Promise<void> {
      return new Promise(async (resolve, reject) => {
         const currentRoutes = RuntimeManager.currentRoutes;
         const statePayload = JSON.stringify({ state: { key, value } });
         const promises = [];

         for (const targetID in currentRoutes) {
            if (!Object.hasOwn(currentRoutes, targetID)) continue;

            const { route } = currentRoutes[targetID];

            const prom = fetch(route, {
               headers: {
                  "X-Requested-With": "PHPSPA_REQUEST",
                  Authorization: `Bearer ${utf8ToBase64(statePayload)}`,
               },
               mode: "same-origin",
               redirect: "follow",
               keepalive: true,
            });
            promises.push(prom);
         }

         const responses = await Promise.all(promises);

         responses.forEach(async (response) => {
            try {
               const responseText = await response.text();
               let responseData;

               // --- Parse response as JSON if possible ---
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
            } catch (error) {
               reject(error);
               handleStateError(error);
            }
         });


         /**
          * Handles errors during state update requests
          */
         function handleStateError(error: any) {
            if (error?.response) {
               error.response
                  .text()
                  .then((fallbackResponse: any) => {
                     let errorData;

                     try {
                        errorData = fallbackResponse?.trim().startsWith("{")
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
         function updateContent(responseData: ComponentObject|string) {
         const component: ComponentObject = typeof responseData === 'string'
            ? { content: responseData }
            : responseData;
   
            // --- Update title if provided ---
            if (component?.title && String(component.title).length > 0) {
               document.title = component.title;
            }

            // --- Find target element and update content ---
            const targetElement =
               document.getElementById(component?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            const updateDOM = () => {
               try {
                  morphdom(targetElement, '<div>' + component.content + '</div>', {
                     childrenOnly: true
                  });
               } catch {
                  targetElement.innerHTML = component.content;
               }
            };

            const completedDOMUpdate = () => {
               // --- Trigger effects for the changed key ---
               RuntimeManager.triggerEffects(key, value);
            };

            updateDOM();
            completedDOMUpdate();
         }
      });
   }

   /**
    * Reloads the current component content while preserving scroll position.
    * Useful for refreshing dynamic content without full page navigation.
    */
   static reloadComponent() {

      // --- Fetch current page content ---
      fetch(location.toString(), {
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

                  // --- Parse response ---
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
       */
      function handleComponentError(error: any) {
         if (error?.response) {
            error.response
               .text()
               .then((fallbackResponse: string) => {
                  let errorData;

                  try {
                     errorData = fallbackResponse?.trim().startsWith("{")
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
       */
      function updateComponentContent(responseData: ComponentObject|string) {
         const component: ComponentObject = typeof responseData === 'string'
            ? { content: responseData }
            : responseData;

         // --- Update title if provided ---
         if (component?.title && String(component.title).length > 0) {
            document.title = component.title;
         }

         // --- Find target and update content ---
         const targetElement =
            document.getElementById(component?.targetID) ??
            document.getElementById(history.state?.targetID) ??
            document.body;

         const updateDOM = () => {
            try {
               morphdom(targetElement, '<div>' + component.content + '</div>', {
                  childrenOnly: true
               });
            } catch {
               targetElement.innerHTML = component.content;
            }
         };

         const completedDOMUpdate = () => {
            // --- Clear old executed scripts cache ---
            RuntimeManager.clearEffects();
            RuntimeManager.clearExecutedScripts();

            // --- Execute any inline scripts and styles in the new content ---
            RuntimeManager.runAll();

            // --- Set up next auto-reload if specified ---
            if (component?.reloadTime) {
               setTimeout(AppManager.reloadComponent, component.reloadTime);
            }
         }

         updateDOM();
         completedDOMUpdate();
      }
   }

   /**
    * Makes an authenticated call to the server with a token and arguments.
    * Used for server-side function calls from the client.
    *
    * @param token - The authentication token for the call
    * @param args - Arguments to pass to the server function
    * @returns The decoded response from the server
    */
   public static async __call(token: string, ...args: any[]): Promise<StateValueType> {
      const callPayload = JSON.stringify({ __call: { token, args } });

      try {
         const response = await fetch(location.pathname, {
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

         // --- Parse and decode response ---
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
      } catch (error: any) {
         // --- Handle errors with response bodies ---
         if (error?.response) {
            try {
               const fallbackResponse = await error.response.text();
               let errorData;

               try {
                  errorData = fallbackResponse?.trim().startsWith("{")
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
            // --- Network errors or other issues ---
            return "";
         }
      }
   }
}