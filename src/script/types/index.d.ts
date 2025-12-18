import AppManager from "./core/AppManager";
declare global {
    export interface Window {
        phpspa: typeof AppManager;
        setState: typeof AppManager.setState;
        useEffect: typeof AppManager.useEffect;
        __call: typeof AppManager.__call;
    }
}
export default AppManager;
//# sourceMappingURL=index.d.ts.map