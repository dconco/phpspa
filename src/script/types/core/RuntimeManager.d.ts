import { CurrentRoutesObject, EventObject, EventPayload } from "../types/RuntimeInterfaces";
import { StateObject } from "../types/StateObjectTypes";
/**
 * Runtime Manager for PhpSPA
 *
 * Handles script execution, style injection, event management, and browser history
 * for the PhpSPA framework. Uses an obscure class name to avoid conflicts.
 */
export declare class RuntimeManager {
    /**
     * Tracks executed scripts to prevent duplicates
     */
    private static executedScripts;
    /**
     * Tracks executed styles to prevent duplicates
     */
    private static executedStyles;
    /**
     * A static cache object that stores processed script content to avoid redundant processing.
     * Used to improve performance by caching scripts that have already been processed or compiled.
     */
    private static ScriptsCachedContent;
    /**
     * This contains all routes for the current page
     */
    static currentRoutes: CurrentRoutesObject;
    static events: EventObject;
    /**
     * Caches the last payload for each emitted event so late listeners can replay it
     */
    private static lastEventPayload;
    private static effects;
    private static memoizedCallbacks;
    /**
     * Registers a side effect to be executed when state changes
     * similar to React's useEffect but using state keys strings as dependencies
     *
     * @param {Function} callback - The effect callback
     * @param {Array<string>} dependencies - Array of state keys to listen for
     */
    static registerEffect(callback: () => void | (() => void), dependencies?: string[] | null): void;
    /**
     * Triggers effects that depend on the specific state key
     *
     * @param key - The state key that changed
     * @param value - The new value (optional)
     */
    static triggerEffects(key: string, value: any): void;
    /**
     * Clears all registered effects and runs their cleanup functions
     */
    static clearEffects(): void;
    private static depsEqual;
    static registerCallback<T extends (...args: any[]) => any>(callback: T, dependencies?: unknown[]): T;
    static runAll(): void;
    /**
     * Processes and executes inline scripts within a container
     * Creates isolated scopes using IIFE to prevent variable conflicts
     */
    private static runInlineScripts;
    static runPhpSpaScripts(container: HTMLElement): void;
    /**
     * Clears all executed scripts from the runtime manager.
     * This method removes all entries from the executedScripts collection,
     * effectively resetting the tracking of previously executed scripts.
     *
     * @static
     * @memberof RuntimeManager
     */
    static clearExecutedScripts(): void;
    /**
     * Processes and injects inline styles within a container
     * Prevents duplicate style injection by tracking content hashes
     */
    private static runInlineStyles;
    /**
     * Emits a custom event to all registered listeners
     * Used for lifecycle events like 'beforeload' and 'load'
     *
     * @param eventName - The name of the event to emit
     * @param payload - The data to pass to event listeners
     */
    static emit(eventName: keyof EventObject, payload: EventPayload): void;
    /**
     * Returns the last cached payload for an event, if available
     */
    static getLastEventPayload(eventName: keyof EventObject): EventPayload | undefined;
    /**
     * Safely pushes a new state to browser history
     * Wraps in try-catch to handle potential browser restrictions
     */
    static pushState(data: StateObject, unused: string, url?: string | URL | null): void;
    /**
     * Safely replaces current browser history state
     * Wraps in try-catch to handle potential browser restrictions
     */
    static replaceState(data: StateObject, unused: string, url?: string | URL | null): void;
}
//# sourceMappingURL=RuntimeManager.d.ts.map