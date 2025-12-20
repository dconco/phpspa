import AppManager from "./core/AppManager";
import type { EventObject } from "./types/RuntimeInterfaces";
declare global {
    export interface Window {
        phpspa: typeof AppManager;
        setState: typeof AppManager.setState;
        useEffect: typeof AppManager.useEffect;
        __call: typeof AppManager.__call;
    }
}
export declare const setState: typeof AppManager.setState;
export declare const useEffect: typeof AppManager.useEffect;
export declare const __call: typeof AppManager.__call;
export type { EventObject, EventPayload } from "./types/RuntimeInterfaces";
export type { StateObject } from "./types/StateObjectTypes";
export type EventName = keyof EventObject;
export type PhpSPAInstance = typeof AppManager;
export default AppManager;
//# sourceMappingURL=index.d.ts.map