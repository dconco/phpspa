import AppManager from "./core/AppManager";
import RuntimeManager from "./core/RuntimeManager";
import { bootstrapPhpSPA } from "./helpers/bootstrap";
import { StateObject } from "./types/StateObjectTypes";
import morphdom from "morphdom";

declare global {
   export interface Window {
      phpspa: typeof AppManager;
      setState: typeof AppManager.setState;
      useEffect: typeof AppManager.useEffect;
      __call: typeof AppManager.__call;
   }
}

/**
 * Ensure bootstrap runs even if script loads after DOMContentLoaded
 */
const readyStates = ["interactive", "complete"];

if (document.readyState === "loading") {
   window.addEventListener("DOMContentLoaded", bootstrapPhpSPA, { once: true });
} else if (readyStates.includes(document.readyState)) {
   bootstrapPhpSPA();
}


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
   if (typeof window.phpspa !== "object") {
      window.phpspa = AppManager;
   }

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

export const setState = AppManager.setState.bind(AppManager);
export const useEffect = AppManager.useEffect.bind(AppManager);
export const __call = AppManager.__call.bind(AppManager);

export default AppManager;
