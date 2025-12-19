import { StateValueType } from "../types/StateObjectTypes";
import { EventObject, EventPayload } from "../types/RuntimeInterfaces";
export default class AppManager {
    /**
     * Navigates to a given URL using PHPSPA's custom navigation logic.
     * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
     * emits lifecycle events, and executes inline scripts.
     *
     * @param url - The URL or path to navigate to.
     * @param state Determines whether to push or replace the browser history state.
     *
     * @fires AppManager#beforeload - Emitted before loading the new route.
     * @fires AppManager#load - Emitted after attempting to load the new route, with success or error status.
     */
    static navigate(url: URL | string, state?: 'push' | 'replace'): void;
    /**
     * Navigates back in the browser history.
     * Uses the native browser history API.
     */
    static back(): void;
    /**
     * Navigates forward in the browser's session history.
     * Uses the native browser history API.
     */
    static forward(): void;
    /**
     * Reloads the current page by navigating to the current URL using the "replace" history mode.
     * This does not add a new entry to the browser's history stack.
     */
    static reload(): void;
    /**
     * Registers a callback function to be executed when the specified event is triggered.
     *
     * @param event - The name of the event to listen for.
     * @param callback - The function to call when the event is triggered.
     */
    static on(event: keyof EventObject, callback: (payload: EventPayload) => void): void;
    /**
     * Registers a side effect to be executed after component updates.
     * Alias for RuntimeManager.registerEffect.
     *
     * @param callback - The effect callback
     * @param dependencies - Array of state keys to listen for
     */
    static useEffect(callback: () => void | (() => void), dependencies?: string[] | null): void;
    /**
     * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
     * Preserves the current scroll position during the update.
     *
     * @param key - The key representing the state to update.
     * @param value - The new value to set for the specified state key.
     * @returns A promise that resolves when the state is updated successfully.
     *
     * @example
     * AppManager.setState('user', { name: 'Alice' })
     *   .then(() => console.log('State updated!'))
     *   .catch(err => console.error('Failed to update state:', err));
     */
    static setState(key: string, value: StateValueType): Promise<void>;
    /**
     * Reloads the current component content while preserving scroll position.
     * Useful for refreshing dynamic content without full page navigation.
     */
    static reloadComponent(): void;
    /**
     * Makes an authenticated call to the server with a token and arguments.
     * Used for server-side function calls from the client.
     *
     * @param token - The authentication token for the call
     * @param args - Arguments to pass to the server function
     * @returns The decoded response from the server
     */
    static __call(token: string, ...args: any[]): Promise<StateValueType>;
}
//# sourceMappingURL=AppManager.d.ts.map