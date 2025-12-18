import AppManager from "./core/AppManager";
import RuntimeManager from "./core/RuntimeManager";
import { StateObject } from "./types/StateObjectTypes";
import { base64ToUtf8 } from "./utils/baseConverter";
import morphdom from "morphdom";
import phpspa from "./types";

declare global {
   export interface Window {
      phpspa: typeof phpspa;
      setState: typeof phpspa.setState;
      useEffect: typeof phpspa.useEffect;
      __call: typeof phpspa.__call;
   }
}

/**
 * Initialize PhpSPA when DOM is ready
 * Sets up the initial browser history state with the current page content
 */
window.addEventListener("DOMContentLoaded", () => {
   const targetElement = document.querySelector("[data-phpspa-target]");
   const targetElementInfo = document.querySelector("[phpspa-target-data]");
   const uri = location.toString();

   RuntimeManager.emit('load', {
      route: uri,
      success: true,
      error: false
   });

   if (targetElement) {
      /**
       *  Create initial state object with current page data
       */
      const initialState: StateObject = {
         url: uri,
         title: document.title,
         targetID: targetElement.id,
         content: targetElement.innerHTML,
         root: true,
      };

      // --- Check if component has auto-reload functionality ---
      if (targetElement.hasAttribute("phpspa-reload-time")) {
         initialState.reloadTime = Number(
            targetElement.getAttribute("phpspa-reload-time")
         );
      }

      // --- Check if component has target info ---
      if (targetElementInfo) {
         const targetData = targetElementInfo.getAttribute("phpspa-target-data");

         // --- This is the json type coming from the server
         type StateDataType = {
            targetIDs: string[],
            currentRoutes: string[],
            defaultContent: string[],
            exact: boolean[],
         }

         const targetDataInfo: StateDataType = JSON.parse(base64ToUtf8(targetData ?? ''));

         targetDataInfo.targetIDs.forEach((targetID: string, index: number) => {
            const exact = targetDataInfo.exact[index];
            const defaultContent = targetDataInfo.defaultContent[index];

            if (targetID === targetElement.id) {
               initialState['exact'] = exact;
               initialState['defaultContent'] = defaultContent;
            }

            RuntimeManager.currentRoutes[targetID] = {
               route: new URL(targetDataInfo.currentRoutes[index], uri),
               defaultContent,
               exact
            }
         })
      }

      // --- Replace current history state with PhpSPA data ---
      RuntimeManager.replaceState(
         initialState,
         document.title,
         uri
      );

      // --- Set up auto-reload if specified ---
      if (initialState.reloadTime) {
         setTimeout(AppManager.reloadComponent, initialState.reloadTime);
      }
   }
});


/**
 * Handle browser back/forward button navigation
 * Restores page content when user navigates through browser history
 */
window.addEventListener("popstate", (event: PopStateEvent) => {
   const navigationState: StateObject = event.state;

   RuntimeManager.emit('beforeload', { route: location.toString() });

   // --- Enable automatic scroll restoration ---
   history.scrollRestoration = "auto";

   // --- Check if we have valid PhpSPA state data ---
   if (navigationState && navigationState.content) {
      // --- Restore page title ---
      document.title = navigationState.title ?? document.title;

      // --- Find target container or fallback to body ---
      const targetContainer =
         document.getElementById(navigationState.targetID);

      if (!targetContainer) {
         location.reload();
         return;
      }

      if (navigationState.targetID) {
         RuntimeManager.currentRoutes[navigationState.targetID] = {
            route: new URL(navigationState.url),
            exact: navigationState.exact ?? false,
            defaultContent: navigationState.defaultContent || ''
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
         if (targetInfo.exact === true && targetID !== navigationState.targetID) {
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

      // --- Decode and restore HTML content ---
      const updateDOM = () => {
         try {
            morphdom(targetContainer, '<div>' + navigationState.content + '</div>', {
               childrenOnly: true
            });
         } catch {
            targetContainer.innerHTML = navigationState.content;
         }
      }

      const completedDOMUpdate = () => {
         // --- Clear old executed scripts cache ---
         RuntimeManager.clearEffects();
         RuntimeManager.clearExecutedScripts();

         // --- Execute any inline scripts and styles in the restored content ---
         RuntimeManager.runAll();

         // --- Restart auto-reload timer if needed ---
         if (navigationState?.reloadTime) {
            setTimeout(AppManager.reloadComponent, navigationState.reloadTime);
         }

         RuntimeManager.emit('load', {
            route: navigationState.url,
            success: true,
            error: false
         });
      }

      if (document.startViewTransition) {
         document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
            RuntimeManager.emit('load', {
               route: location.href,
               success: false,
               error: reason || 'Unknown error during view transition',
            });
         });
      } else {
         updateDOM();
         completedDOMUpdate();
      }

   } else {
      // --- No valid state found - reload current URL to refresh ---
      location.reload();
   }
});


if (typeof window !== "undefined") {
   if (typeof window.setState !== "function") {
      window.setState = AppManager.setState;
   }

   if (typeof window.__call !== "function") {
      window.__call = AppManager.__call;
   }

   if (typeof window.useEffect !== "function") {
      window.useEffect = AppManager.useEffect;
   }
}


export default AppManager;
