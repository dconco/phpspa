import { AppManager } from "../core/AppManager"
import { RuntimeManager } from "../core/RuntimeManager"
import { StateObject, TargetInformation } from "../types/StateObjectTypes"
import { base64ToUtf8 } from "../utils/baseConverter"
import { setupLinkInterception } from "../utils/setupLinkInterception"

/**
 * Bootstraps PhpSPA runtime by caching current route info
 * and wiring up history/navigation handlers
 */
export function bootstrap() {
   const targetElement = document.querySelector("[data-phpspa-target]")
   const targetElementInfo = document.querySelector("[phpspa-target-data]")
   const uri = location.toString()

   RuntimeManager.emit('load', {
      route: uri,
      success: true,
      error: false
   })

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
      }

      // --- Check if component has target info ---
      if (targetElementInfo) {
         const targetData = targetElementInfo.getAttribute("phpspa-target-data")

         const targetDataInfo: TargetInformation = JSON.parse(base64ToUtf8(targetData ?? ''))

         // --- Check if component has auto-reload functionality ---
         if (targetDataInfo.reloadTime) initialState.reloadTime = targetDataInfo.reloadTime

         RuntimeManager.currentStateData = targetDataInfo.stateData;

         targetDataInfo.targetIDs.forEach((targetID: string, index: number) => {
            const exact = targetDataInfo.exact[index]
            const defaultContent = targetDataInfo.defaultContent[index]

            if (targetID === targetElement.id) {
               initialState.exact = exact
               initialState.defaultContent = defaultContent
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
      )

      // --- Set up auto-reload if specified ---
      if (initialState.reloadTime) {
         setTimeout(AppManager.reloadComponent, initialState.reloadTime)
      }
   }

   setupLinkInterception()
}
