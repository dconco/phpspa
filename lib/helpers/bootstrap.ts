import AppManager from "../core/AppManager";
import RuntimeManager from "../core/RuntimeManager";
import { StateObject } from "../types/StateObjectTypes";
import { base64ToUtf8 } from "../utils/baseConverter";
import { setupLinkInterception } from "../utils/setupLinkInterception";

/**
 * Bootstraps PhpSPA runtime by caching current route info
 * and wiring up history/navigation handlers
 */
export function bootstrapPhpSPA() {
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

   setupLinkInterception();
}
