import AppManager from "./core/AppManager";
import phpspa from "./types";
declare global {
    export interface Window {
        phpspa: typeof phpspa;
        setState: typeof phpspa.setState;
        useEffect: typeof phpspa.useEffect;
        __call: typeof phpspa.__call;
    }
}
export default AppManager;
//# sourceMappingURL=index.d.ts.map