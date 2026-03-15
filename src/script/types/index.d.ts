import { AppManager } from "./core/AppManager";
import type { EventObject } from "./types/RuntimeInterfaces";
export default class phpspa extends AppManager {
}
declare global {
    export interface Window {
        phpspa: typeof phpspa;
        setState: typeof phpspa.setState;
        useEffect: typeof phpspa.useEffect;
        useCallback: typeof phpspa.useCallback;
        __call: typeof phpspa.__call;
    }
}
export declare const setState: typeof AppManager.setState;
export declare const useEffect: typeof AppManager.useEffect;
export declare const useCallback: typeof AppManager.useCallback;
export declare const __call: typeof AppManager.__call;
export type { EventObject, EventPayload } from "./types/RuntimeInterfaces";
export type { StateObject } from "./types/StateObjectTypes";
export type EventName = keyof EventObject;
export type PhpSPAInstance = typeof phpspa;
//# sourceMappingURL=index.d.ts.map