import AppManager from "./core/AppManager";
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
export default AppManager;
//# sourceMappingURL=index.d.ts.map