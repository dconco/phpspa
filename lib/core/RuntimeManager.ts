import { CurrentRoutesObject, EffectType, EventObject, EventPayload } from "../types/RuntimeInterfaces"
import { StateObject, StateValueType } from "../types/StateObjectTypes"
import { utf8ToBase64 } from "../utils/baseConverter"


/**
 * Runtime Manager for PhpSPA
 *
 * Handles script execution, style injection, event management, and browser history
 * for the PhpSPA framework. Uses an obscure class name to avoid conflicts.
 */
export class RuntimeManager {
   /**
    * Tracks executed styles to prevent duplicates
    */
   public static executedStyles: Set<string> = new Set()

   /**
    * A static cache object that stores processed script content to avoid redundant processing.
    * Used to improve performance by caching scripts that have already been processed or compiled.
    */
   private static ScriptsCachedContent: Record<string, string> = {}

   /**
    * This contains all routes for the current page
    */
   public static currentRoutes: CurrentRoutesObject = {}

   public static events: EventObject = {
      beforeload: [],
      load: [],
   }

   public static currentStateData: Record<string, StateValueType>

   /**
    * Caches the last payload for each emitted event so late listeners can replay it
    */
   private static lastEventPayload: Partial<Record<keyof EventObject, EventPayload>> = {}

   private static effects: Set<EffectType> = new Set()

   private static memoizedCallbacks: Array<{ deps: unknown[]; resolvedDeps: unknown[]; callback: (...args: unknown[]) => unknown }> = []

   /**
    * Registers a side effect to be executed when state changes
    * similar to React's useEffect but using state keys strings as dependencies
    *
    * @param {Function} callback - The effect callback
    * @param {Array<string>} dependencies - Array of state keys to listen for
    */
   public static registerEffect(callback: () => void | (() => void), dependencies: unknown[]|null = null): void {
      // --- Run immediately (mount) ---
      const cleanup = callback()

      const effect: EffectType = {
         callback,
         dependencies,
         cleanup: typeof cleanup === 'function' ? cleanup : null,
         lastDeps: dependencies ? RuntimeManager.resolveDependencies(dependencies) : null
      }

      RuntimeManager.effects.add(effect)
   }

   /**
    * Triggers effects that depend on the specific state key
    *
    * @param key - The state key that changed
    * @param value - The new value (optional)
    */
   public static triggerEffects(key: string, value: any): void {
      RuntimeManager.effects.forEach(effect => {
         if (!effect.dependencies || effect.dependencies.length === 0) {
            RuntimeManager.invokeEffect(effect, effect.dependencies)
            return
         }

         const nextDeps = RuntimeManager.resolveDependencies(effect.dependencies)
         if (!effect.lastDeps || !RuntimeManager.depsEqual(effect.lastDeps, nextDeps)) {
            RuntimeManager.invokeEffect(effect, nextDeps)
         }
      })
   }

   /**
    * Clears all registered effects and runs their cleanup functions
    */
   public static clearEffects(): void {
      RuntimeManager.effects.forEach(effect => {
         if (effect.cleanup) effect.cleanup()
      })
      RuntimeManager.effects.clear()
   }

   private static depsEqual(a: unknown[]|null, b: unknown[]|null): boolean {
      if (a === b) return true
      if (!a || !b) return false
      if (a.length !== b.length) return false
      return a.every((dep, index) => Object.is(dep, b[index]))
   }

   public static registerCallback<T extends (...args: any[]) => any>(callback: T, dependencies: unknown[] = []): T {
      const resolvedDeps = RuntimeManager.resolveDependencies(dependencies)
      const existing = RuntimeManager.memoizedCallbacks.find(entry =>
         entry.deps.length === dependencies.length && RuntimeManager.depsEqual(entry.resolvedDeps, resolvedDeps)
      )

      if (existing) {
         return existing.callback as T
      }

      const memoized = callback.bind(undefined) as T
      RuntimeManager.memoizedCallbacks.push({ deps: dependencies.slice(), resolvedDeps, callback: memoized })
      return memoized
   }

   private static resolveDependencies(dependencies: unknown[]): unknown[] {
      return dependencies.map(dep => RuntimeManager.resolveDependency(dep))
   }

   private static resolveDependency(dependency: unknown): unknown {
      if (
         typeof dependency === 'string' &&
         RuntimeManager.currentStateData &&
         Object.prototype.hasOwnProperty.call(RuntimeManager.currentStateData, dependency)
      ) {
         return RuntimeManager.currentStateData[dependency]
      }

      return dependency
   }

   private static invokeEffect(effect: EffectType, nextDeps: unknown[]|null): void {
      if (effect.cleanup) effect.cleanup()

      const cleanup = effect.callback()
      effect.cleanup = typeof cleanup === 'function' ? cleanup : null

      effect.lastDeps = nextDeps ? nextDeps.slice() : nextDeps
   }

   public static runScripts(): void {
      for (const targetID in RuntimeManager.currentRoutes) {
         const element = document.getElementById(targetID)

         if (element) {
            this.runScriptsForElement(element)
         }
      }
   }

   public static runStyles(): void {
      for (const targetID in RuntimeManager.currentRoutes) {
         const element = document.getElementById(targetID)

         if (element) {
            this.runStylesForElement(element)
         }
      }
   }

   public static runScriptsForElement(element: HTMLElement): void {
      this.runPhpSpaScripts(element)
   }

   public static runStylesForElement(element: HTMLElement): void {
      this.runInlineStyles(element)
   }

   /**
    * Processes and executes inline scripts within a container
    * Creates isolated scopes using IIFE to prevent variable conflicts
    */
   private static runInlineScripts(container: HTMLElement) {
      const scripts = container.querySelectorAll("script")
      const nonce = document.head.getAttribute('x-phpspa')

      scripts.forEach((script: HTMLScriptElement) => {
         const src = script.getAttribute('src')
         const typeAttr = (script.getAttribute('type') ?? '').trim().toLowerCase()
         const isModule = typeAttr === 'module'
         const isExecutable =
            typeAttr === '' ||
            typeAttr === 'text/javascript' ||
            typeAttr === 'application/javascript' ||
            typeAttr === 'application/ecmascript' ||
            typeAttr === 'text/ecmascript' ||
            isModule

         if (src) {
            const newScript = document.createElement("script")
            newScript.nonce = nonce ?? undefined

            for (const attribute of Array.from(script.attributes)) {
               newScript.setAttribute(attribute.name, attribute.value)
            }

            document.head.appendChild(newScript).remove()

            return
         }

         if (!isExecutable) {
            return
         }

         // --- Create new script element ---
         const newScript = document.createElement("script")

         newScript.nonce = nonce ?? undefined;

         // --- Copy all attributes except the data-type identifier ---
         for (const attribute of Array.from(script.attributes)) {
            newScript.setAttribute(attribute.name, attribute.value)
         }

         newScript.textContent = script.textContent

         // --- Execute and immediately remove from DOM ---
         document.head.appendChild(newScript).remove()
      })
   }


   private static runPhpSpaScripts(container: HTMLElement) {
      const scripts = container.querySelectorAll("phpspa-script, script") as NodeListOf<HTMLScriptElement>
      const nonce = document.head.getAttribute('x-phpspa')

      scripts.forEach(async (script: HTMLScriptElement): Promise<void> => {
         const scriptUrl = script.getAttribute('src')
         const typeAttr = (script.getAttribute('type') ?? '').trim().toLowerCase()
         const isModule = typeAttr === 'module'
         const isExecutable =
            typeAttr === '' ||
            typeAttr === 'text/javascript' ||
            typeAttr === 'application/javascript' ||
            typeAttr === 'application/ecmascript' ||
            typeAttr === 'text/ecmascript' ||
            isModule

         
         if (!isExecutable) {
            const newScript = document.createElement("script")

            for (const attribute of Array.from(script.attributes)) {
               newScript.setAttribute(attribute.name, attribute.value)
            }
            newScript.textContent = script.textContent

            if (!newScript.getAttribute('nonce')) newScript.nonce = nonce ?? undefined

            // --- Execute and immediately remove from DOM ---
            return document.head.appendChild(newScript).remove()
         }

         if (!scriptUrl) {
            // --- Create new script element ---
            const newScript = document.createElement("script")

            // --- Copy all attributes ---
            for (const attribute of Array.from(script.attributes)) {
               newScript.setAttribute(attribute.name, attribute.value)
            }

            if (!newScript.getAttribute('nonce')) newScript.nonce = nonce ?? undefined

            newScript.textContent = script.textContent

            // --- Execute and immediately remove from DOM ---
            return document.head.appendChild(newScript).remove()
         }

         // --- Check cache first, then execute the cached content else download the script ---
         if (this.ScriptsCachedContent[scriptUrl]) {
            const newScript = document.createElement("script")

            newScript.textContent = this.ScriptsCachedContent[scriptUrl]

            for (const attribute of Array.from(script.attributes)) {
               if (attribute.name == 'src') continue
               newScript.setAttribute(attribute.name, attribute.value)
            }

            if (!newScript.getAttribute('nonce')) newScript.nonce = nonce ?? undefined

            // --- Execute and immediately remove from DOM ---
            return document.head.appendChild(newScript).remove()
         }

         const response = await fetch(scriptUrl, {
            headers: {
               "X-Requested-With": "PHPSPA_REQUEST_SCRIPT",
            }
         })

         if (response.ok) {
            const scriptContent = await response.text()

            // --- Create new script element ---
            const newScript = document.createElement("script")
            newScript.textContent = scriptContent;

            for (const attribute of Array.from(script.attributes)) {
               if (attribute.name == 'src') continue
               newScript.setAttribute(attribute.name, attribute.value)
            }

            if (!newScript.getAttribute('nonce')) newScript.nonce = nonce ?? undefined

            // --- Execute and immediately remove from DOM ---
            document.head.appendChild(newScript).remove()

            // --- Cache the fetched script content ---
            this.ScriptsCachedContent[scriptUrl] = scriptContent;
         } else {
            console.error(`Failed to load script from ${scriptUrl}: ${response.statusText}`);
         }
      })
   }


   /**
    * Processes and injects inline styles within a container
    * Prevents duplicate style injection by tracking content hashes
    */
   private static runInlineStyles(container: HTMLElement) {
      const styles = container.querySelectorAll("style")
      const nonce = document.head.getAttribute('x-phpspa')

      styles.forEach((style: HTMLStyleElement) => {
         // --- Use base64 encoded content as unique identifier ---
         const contentHash = utf8ToBase64(style.textContent.trim())

         // --- Skip if this style has already been injected ---
         if (!this.executedStyles.has(contentHash) && style.textContent.trim() !== "") {
            this.executedStyles.add(contentHash)

            // --- Create new style element ---
            const newStyle = document.createElement("style")
            newStyle.nonce = nonce ?? undefined

            // --- Copy all attributes except the data-type identifier ---
            for (const attribute of Array.from(style.attributes)) {
               newStyle.setAttribute(attribute.name, attribute.value)
            }

            // --- Copy style content and inject into head ---
            newStyle.textContent = style.textContent
            document.head.appendChild(newStyle).remove()
         }
      })
   }

   /**
    * Emits a custom event to all registered listeners
    * Used for lifecycle events like 'beforeload' and 'load'
    *
    * @param eventName - The name of the event to emit
    * @param payload - The data to pass to event listeners
    */
   static emit(eventName: keyof EventObject, payload: EventPayload) {
      const callbacks = this.events[eventName] || []
      this.lastEventPayload[eventName] = payload

      // --- Execute all registered callbacks for this event ---
      for (const callback of callbacks) {
         if (typeof callback === "function") {
            try {
               callback(payload)
            } catch (error) {
               // --- Log callback errors but don't break the chain ---
               console.error(`Error in ${eventName} event callback:`, error)
            }
         }
      }
   }

   /**
    * Returns the last cached payload for an event, if available
    */
   public static getLastEventPayload(eventName: keyof EventObject): EventPayload | undefined {
      return this.lastEventPayload[eventName]
   }

   /**
    * Safely pushes a new state to browser history
    * Wraps in try-catch to handle potential browser restrictions
    */
   public static pushState(data: StateObject, unused: string, url?: string | URL | null) {
      try {
         history.pushState(data, unused, url)
      } catch (error) {
         // --- Silently handle history API restrictions ---
         console.warn("Failed to push history state:", error instanceof Error ? error.message : error)
      }
   }

   /**
    * Safely replaces current browser history state
    * Wraps in try-catch to handle potential browser restrictions
    */
   public static replaceState(data: StateObject, unused: string, url?: string | URL | null) {
      try {
         history.replaceState(data, unused, url)
      } catch (error) {
         // --- Silently handle history API restrictions ---
         console.warn("Failed to replace history state:", error instanceof Error ? error.message : error)
      }
   }
}
