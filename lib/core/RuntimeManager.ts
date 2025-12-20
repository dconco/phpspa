import { CurrentRoutesObject, EffectType, EventObject, EventPayload } from "../types/RuntimeInterfaces";
import { StateObject } from "../types/StateObjectTypes";
import { utf8ToBase64 } from "../utils/baseConverter";


/**
 * Runtime Manager for PhpSPA
 *
 * Handles script execution, style injection, event management, and browser history
 * for the PhpSPA framework. Uses an obscure class name to avoid conflicts.
 */
export class RuntimeManager {
   /**
    * Tracks executed scripts to prevent duplicates
    */
   private static executedScripts: Set<string> = new Set();

   /**
    * Tracks executed styles to prevent duplicates
    */
   private static executedStyles: Set<string> = new Set();

   /**
    * A static cache object that stores processed script content to avoid redundant processing.
    * Used to improve performance by caching scripts that have already been processed or compiled.
    */
   private static ScriptsCachedContent: Record<string, string> = {};

   /**
    * This contains all routes for the current page
    */
   public static currentRoutes: CurrentRoutesObject = {};

   public static events: EventObject = {
      beforeload: [],
      load: [],
   };

   /**
    * Caches the last payload for each emitted event so late listeners can replay it
    */
   private static lastEventPayload: Partial<Record<keyof EventObject, EventPayload>> = {};

   private static effects: Set<EffectType> = new Set();

   private static memoizedCallbacks: Array<{ deps: unknown[]; callback: (...args: unknown[]) => unknown }> = [];

   /**
    * Registers a side effect to be executed when state changes
    * similar to React's useEffect but using state keys strings as dependencies
    *
    * @param {Function} callback - The effect callback
    * @param {Array<string>} dependencies - Array of state keys to listen for
    */
   public static registerEffect(callback: () => void | (() => void), dependencies: string[]|null = null): void {
      // --- Run immediately (mount) ---
      const cleanup = callback();

      const effect: EffectType = {
         callback,
         dependencies,
         cleanup: typeof cleanup === 'function' ? cleanup : null
      };

      RuntimeManager.effects.add(effect);
   }

   /**
    * Triggers effects that depend on the specific state key
    *
    * @param key - The state key that changed
    * @param value - The new value (optional)
    */
   public static triggerEffects(key: string, value: any): void {
      RuntimeManager.effects.forEach(effect => {
         if (effect.dependencies === null || effect.dependencies.includes(key)) {
            // --- Run cleanup if exists ---
            if (effect.cleanup) effect.cleanup();

            // --- Re-run callback ---
            const cleanup = effect.callback();
            effect.cleanup = typeof cleanup === 'function' ? cleanup : null;
         }
      });
   }

   /**
    * Clears all registered effects and runs their cleanup functions
    */
   public static clearEffects(): void {
      RuntimeManager.effects.forEach(effect => {
         if (effect.cleanup) effect.cleanup();
      });
      RuntimeManager.effects.clear();
   }

   private static depsEqual(a: unknown[], b: unknown[]): boolean {
      if (a.length !== b.length) return false;
      return a.every((dep, index) => Object.is(dep, b[index]));
   }

   public static registerCallback<T extends (...args: any[]) => any>(callback: T, dependencies: unknown[] = []): T {
      const existing = RuntimeManager.memoizedCallbacks.find(entry => RuntimeManager.depsEqual(entry.deps, dependencies));

      if (existing) {
         return existing.callback as T;
      }

      const memoized = callback.bind(undefined) as T;
      RuntimeManager.memoizedCallbacks.push({ deps: dependencies.slice(), callback: memoized });
      return memoized;
   }

   public static runAll(): void {
      for (const targetID in RuntimeManager.currentRoutes) {
         const element = document.getElementById(targetID);

         if (element) {
            this.runInlineScripts(element);
            this.runPhpSpaScripts(element);
            this.runInlineStyles(element);
         }
      }
   }

   /**
    * Processes and executes inline scripts within a container
    * Creates isolated scopes using IIFE to prevent variable conflicts
    */
   private static runInlineScripts(container: HTMLElement) {
      const scripts = container.querySelectorAll("script");
      const nonce = document.documentElement.getAttribute('x-phpspa');

      scripts.forEach((script: HTMLScriptElement) => {
         // --- Use base64 encoded content as unique identifier ---
         const contentHash = utf8ToBase64(script.textContent.trim());

         // --- Skip if this script has already been executed ---
         if (!this.executedScripts.has(contentHash) && script.textContent.trim() !== "") {
            this.executedScripts.add(contentHash);

            // --- Create new script element ---
            const newScript = document.createElement("script");

            newScript.nonce = nonce ?? undefined;

            // --- Copy all attributes except the data-type identifier ---
            for (const attribute of Array.from(script.attributes)) {
               newScript.setAttribute(attribute.name, attribute.value);
            }

            // --- Check if script should run in async context ---
            const isAsync = script.hasAttribute("async");

            // --- Wrap in IIFE to create isolated scope ---
            if (isAsync) {
               newScript.textContent = `(async function() {\n${script.textContent}\n})();`;
            } else {
               newScript.textContent = `(function() {\n${script.textContent}\n})();`;
            }

            // --- Execute and immediately remove from DOM ---
            document.head.appendChild(newScript).remove();
         }
      });
   }


   static runPhpSpaScripts(container: HTMLElement) {
      const scripts = container.querySelectorAll("phpspa-script, script[data-type=\"phpspa/script\"]") as NodeListOf<HTMLScriptElement>;

      scripts.forEach(async (script: HTMLScriptElement): Promise<void> => {
         const scriptUrl = script.getAttribute('src') ?? '';
         const scriptType = script.getAttribute('type') ?? '';
         const nonce = document.documentElement.getAttribute('x-phpspa');

         // --- Skip if this script has already been executed ---
         if (!this.executedScripts.has(scriptUrl)) {
            this.executedScripts.add(scriptUrl);

            // --- Check cache first ---
            if (this.ScriptsCachedContent[scriptUrl]) {
               const newScript = document.createElement("script");
               newScript.textContent = this.ScriptsCachedContent[scriptUrl];
               newScript.nonce = nonce ?? undefined;
               newScript.type = scriptType;

               // --- Execute and immediately remove from DOM ---
               document.head.appendChild(newScript).remove();
               return;
            }

            const response = await fetch(scriptUrl, {
               headers: {
                  "X-Requested-With": "PHPSPA_REQUEST_SCRIPT",
               },
            });

            if (response.ok) {
               const scriptContent = await response.text();

               // --- Create new script element ---
               const newScript = document.createElement("script");
               newScript.textContent = scriptContent;
               newScript.nonce = nonce ?? undefined;
               newScript.type = scriptType;

               // --- Execute and immediately remove from DOM ---
               document.head.appendChild(newScript).remove();

               // --- Cache the fetched script content ---
               this.ScriptsCachedContent[scriptUrl] = scriptContent;
            } else {
               console.error(`Failed to load script from ${scriptUrl}: ${response.statusText}`);
            }
         }
      });
   }


   /**
    * Clears all executed scripts from the runtime manager.
    * This method removes all entries from the executedScripts collection,
    * effectively resetting the tracking of previously executed scripts.
    *
    * @static
    * @memberof RuntimeManager
    */
   public static clearExecutedScripts() {
      RuntimeManager.executedScripts.clear();
   }

   /**
    * Processes and injects inline styles within a container
    * Prevents duplicate style injection by tracking content hashes
    */
   private static runInlineStyles(container: HTMLElement) {
      const styles = container.querySelectorAll("style");
      const nonce = document.documentElement.getAttribute('x-phpspa');

      styles.forEach((style: HTMLStyleElement) => {
         // --- Use base64 encoded content as unique identifier ---
         const contentHash = utf8ToBase64(style.textContent.trim());

         // --- Skip if this style has already been injected ---
         if (!this.executedStyles.has(contentHash) && style.textContent.trim() !== "") {
            this.executedStyles.add(contentHash);

            // --- Create new style element ---
            const newStyle = document.createElement("style");
            newStyle.nonce = nonce ?? undefined;

            // --- Copy all attributes except the data-type identifier ---
            for (const attribute of Array.from(style.attributes)) {
               newStyle.setAttribute(attribute.name, attribute.value);
            }

            // --- Copy style content and inject into head ---
            newStyle.textContent = style.textContent;
            document.head.appendChild(newStyle).remove();
         }
      });
   }

   /**
    * Emits a custom event to all registered listeners
    * Used for lifecycle events like 'beforeload' and 'load'
    *
    * @param eventName - The name of the event to emit
    * @param payload - The data to pass to event listeners
    */
   static emit(eventName: keyof EventObject, payload: EventPayload) {
      const callbacks = this.events[eventName] || [];
      this.lastEventPayload[eventName] = payload;

      // --- Execute all registered callbacks for this event ---
      for (const callback of callbacks) {
         if (typeof callback === "function") {
            try {
               callback(payload);
            } catch (error) {
               // --- Log callback errors but don't break the chain ---
               console.error(`Error in ${eventName} event callback:`, error);
            }
         }
      }
   }

   /**
    * Returns the last cached payload for an event, if available
    */
   public static getLastEventPayload(eventName: keyof EventObject): EventPayload | undefined {
      return this.lastEventPayload[eventName];
   }

   /**
    * Safely pushes a new state to browser history
    * Wraps in try-catch to handle potential browser restrictions
    */
   public static pushState(data: StateObject, unused: string, url?: string | URL | null) {
      try {
         history.pushState(data, unused, url);
      } catch (error) {
         // --- Silently handle history API restrictions ---
         console.warn("Failed to push history state:", error instanceof Error ? error.message : error);
      }
   }

   /**
    * Safely replaces current browser history state
    * Wraps in try-catch to handle potential browser restrictions
    */
   public static replaceState(data: StateObject, unused: string, url?: string | URL | null) {
      try {
         history.replaceState(data, unused, url);
      } catch (error) {
         // --- Silently handle history API restrictions ---
         console.warn("Failed to replace history state:", error instanceof Error ? error.message : error);
      }
   }
}
