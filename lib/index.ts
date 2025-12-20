import { AppManager } from "./core/AppManager"
import { bootstrap } from "./helpers/bootstrap"
import type { EventObject } from "./types/RuntimeInterfaces"
import { navigateHistory } from "./helpers/navigateHistrory"

export default class phpspa extends AppManager {}

declare global {
   export interface Window {
      phpspa: typeof phpspa
      setState: typeof phpspa.setState
      useEffect: typeof phpspa.useEffect
      useCallback: typeof phpspa.useCallback
      __call: typeof phpspa.__call
   }
}

// --- Ensure bootstrap runs even if script loads after DOMContentLoaded ---
const readyStates = ["interactive", "complete"]

if (document.readyState === "loading") {
   window.addEventListener("DOMContentLoaded", bootstrap, { once: true })
} else if (readyStates.includes(document.readyState)) {
   bootstrap()
}


// --- Handle browser back/forward button navigation ---
// --- Restores page content when user navigates through browser history ---
window.addEventListener("popstate", navigateHistory)


if (typeof window !== "undefined") {
   window.phpspa = phpspa

   if (window.setState !== phpspa.setState) window.setState = phpspa.setState

   if (window.__call !== phpspa.__call) window.__call = phpspa.__call

   if (window.useEffect !== phpspa.useEffect) window.useEffect = phpspa.useEffect

   if (window.useCallback !== phpspa.useCallback) window.useCallback = phpspa.useCallback
}

export const setState = phpspa.setState.bind(phpspa)
export const useEffect = phpspa.useEffect.bind(phpspa)
export const useCallback = phpspa.useCallback.bind(phpspa)
export const __call = phpspa.__call.bind(phpspa)

export type { EventObject, EventPayload } from "./types/RuntimeInterfaces"
export type { StateValueType } from "./types/StateObjectTypes"
export type EventName = keyof EventObject
export type PhpSPAInstance = typeof phpspa
