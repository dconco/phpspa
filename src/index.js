/**
 * phpSPA JavaScript Engine
 *
 * A lightweight JavaScript engine for PHP-powered single-page applications.
 * Handles SPA-style navigation, content replacement, and lifecycle events
 * without full page reloads. Designed to pair with the `phpSPA` PHP framework.
 *
 * Features:
 * - `phpspa.navigate(url, state = "push")`: Navigate to a new route via AJAX.
 * - `phpspa.back()` / `phpspa.forward()`: Navigate browser history.
 * - `phpspa.on("beforeload" | "load", callback)`: Lifecycle event hooks.
 * - Auto-replaces DOM target with component content and updates `<title>` and `<meta>`.
 * - Executes inline component scripts marked as `<script data-type="phpspa/script">`.
 * - Built-in scroll position restoration across route changes.
 *
 * Example Usage:
 * ```js
 * phpspa.on("beforeload", ({ route }) => showSpinner());
 * phpspa.on("load", ({ success }) => hideSpinner());
 * phpspa.navigate("/profile");
 * ```
 *
 * Note:
 * - All scripts and logic must be attached per component using `$component->script(...)`.
 * - This library assumes server-rendered HTML responses with placeholder target IDs.
 *
 * @author Dave Conco
 * @version 1.0.0
 * @license MIT
 */
(function () {
   window.addEventListener("DOMContentLoaded", () => {
      const target = document.querySelector("[data-phpspa-target]");

      if (target) {
         const state = {
            url: location.href,
            title: document.title,
            targetID: target.parentElement.id,
            content: target.innerHTML,
         };
         history.replaceState(state, document.title, location.href);
      }
   });

   document.addEventListener("click", (ev) => {
      const info = ev.target.closest('a[data-type="phpspa-link-tag"]');

      if (info) {
         ev.preventDefault();
         phpspa.navigate(new URL(info.href, location.href), "push");
      }
   });

   window.addEventListener("popstate", (ev) => {
      const state = ev.state;

      if (state && state.url && state.targetID && state.content) {
         document.title = state.title ?? document.title;

         let targetElement =
            document.getElementById(state.targetID) ?? document.body;

         // if (state.url instanceof URL) {
         //    phpspa.states[state.url.pathname] = [
         //       targetElement,
         //       targetElement.innerHTML,
         //    ];
         // } else {
         //    let url = new URL(state.url, location.href);
         //    phpspa.states[url] = [targetElement, targetElement.innerHTML];
         // }
         targetElement.innerHTML = state.content;
         runInlineStyles(targetElement);
         runInlineScripts(targetElement);
      } else {
         phpspa.navigate(new URL(location.href), "replace");
      }

      history.scrollRestoration = "auto";
   });
})();

/**
 * A static class for managing client-side navigation and state in a PHP-powered Single Page Application (SPA).
 * Provides methods for navigation, history manipulation, event handling, and dynamic content updates.
 *
 * @class phpspa
 *
 * @property {?Function} onLoad - Optional callback to be executed when the SPA is loaded.
 * @property {Object} _events - Internal event registry for custom event handling.
 *
 * @method navigate
 * @static
 * @param {string|URL} url - The URL to navigate to.
 * @param {string} [state="push"] - The history state action ("push" or "replace").
 * @description Fetches content from the given URL using a custom method, updates the DOM, manages history state, and executes inline scripts.
 *
 * @method back
 * @static
 * @description Navigates back in the browser history.
 *
 * @method forward
 * @static
 * @description Navigates forward in the browser history.
 *
 * @method reload
 * @static
 * @description Reloads the current page content via SPA navigation.
 *
 * @method on
 * @static
 * @param {string} event - The event name to listen for.
 * @param {Function} callback - The callback to execute when the event is emitted.
 * @description Registers an event listener for a custom event.
 *
 * @method emit
 * @static
 * @param {string} event - The event name to emit.
 * @param {Object} payload - The data to pass to event listeners.
 * @description Emits a custom event to all registered listeners.
 */
class phpspa {
   // static states = {};

   /**
    * Internal event registry for custom events.
    * @type {Object}
    * @private
    */
   static get _events() {
      return {
         beforeload: [],
         load: [],
      };
   }

   /**
    * Navigates to a given URL using PHPSPA's custom navigation logic.
    * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
    * emits lifecycle events, and executes inline scripts.
    *
    * @param {string|URL} url - The URL or path to navigate to.
    * @param {"push"|"replace"} [state="push"] - Determines whether to push or replace the browser history state.
    *
    * @fires phpspa#beforeload - Emitted before loading the new route.
    * @fires phpspa#load - Emitted after attempting to load the new route, with success or error status.
    */
   static navigate(url, state = "push") {
      (async () => {
         // let initialPath = location.pathname;
         phpspa.emit("beforeload", { route: url });

         const response = await fetch(url, {
            method: "PHPSPA_GET",
            mode: "same-origin",
            keepalive: true,
         });

         response
            .text()
            .then((res) => {
               try {
                  let json = JSON.parse(res);
                  phpspa.emit("load", {
                     route: url,
                     success: true,
                     error: false,
                  });
                  call(json);
               } catch (e) {
                  let data = res ?? "";
                  phpspa.emit("load", { route: url, success: false, error: e });
                  call(data);
               }
            })
            .catch((e) =>
               phpspa.emit("load", { route: url, success: false, error: e })
            );

         function call(data) {
            if (
               "string" === typeof data?.title ||
               "number" === typeof data?.title
            ) {
               document.title = data.title;
            }

            let targetElement =
               document.getElementById(data?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            // phpspa.states[initialPath] = [
            //    targetElement,
            //    targetElement.innerHTML,
            // ];
            targetElement.innerHTML = data?.content ?? data;
            // phpspa.states[url.pathname] = [
            //    targetElement,
            //    data?.content ?? data,
            // ];

            const stateData = {
               url: url?.href ?? url,
               title: data?.title ?? document.title,
               targetID: data?.targetID ?? targetElement.id,
               content: data?.content ?? data,
            };

            if (state === "push") {
               history.pushState(stateData, stateData.title, url);
            } else if (state === "replace") {
               history.replaceState(stateData, stateData.title, url);
            }

            let hashedElement = document.getElementById(
               url?.hash?.substring(1)
            );

            if (hashedElement) {
               scroll({
                  top: hashedElement.offsetTop,
                  left: hashedElement.offsetLeft,
               });
            }

            runInlineStyles(targetElement);
            runInlineScripts(targetElement);
         }
      })();
   }

   /**
    * Navigates back in the browser history.
    *
    * This static method calls `history.back()` to move the browser to the previous entry in the session history.
    * The commented-out code suggests an intention to manage custom state and content restoration,
    * but currently only the native browser history is used.
    */
   static back() {
      history.back();

      // let [targetElement, content] =
      //    this.states[
      //       Object.keys(this.states).at(
      //          Object.keys(this.states).indexOf(history.state) - 1
      //       )
      //    ];
      // let url = new URL(
      //    Object.keys(this.states).at(
      //       Object.keys(this.states).indexOf(history.state) - 1
      //    ),
      //    location.href
      // );
      // if (!targetElement) {
      //    this.navigate(url, "replace");
      // } else {
      //    targetElement.innerHTML = content;
      //    let hashedElement = document.getElementById(url.hash.substring(1));
      //    if (hashedElement) {
      //       scroll({
      //          top: hashedElement.offsetTop,
      //       });
      //    }
      // }
   }

   /**
    * Navigates forward in the browser's session history.
    *
    * This static method calls the native `history.forward()` function to move the user forward by one entry in the session history stack.
    *
    * Note: The commented-out code suggests additional logic for handling custom state management and DOM updates, but it is currently inactive.
    */
   static forward() {
      history.forward();

      // let [targetElement, content] =
      //    this.states[
      //       Object.keys(this.states).at(
      //          Object.keys(this.states).indexOf(history.state) + 1
      //       )
      //    ];

      // let url = new URL(
      //    Object.keys(this.states).at(
      //       Object.keys(this.states).indexOf(history.state) + 1
      //    ),
      //    location.href
      // );

      // if (!targetElement) {
      //    this.navigate(url, "replace");
      // } else {
      //    targetElement.innerHTML = content;
      //    let hashedElement = document.getElementById(url.hash.substring(1));

      //    if (hashedElement) {
      //       scroll({
      //          top: hashedElement.offsetTop,
      //       });
      //    }
      // }
   }

   /**
    * Reloads the current page by navigating to the current URL using the "replace" history mode.
    * This does not add a new entry to the browser's history stack.
    *
    * @static
    */
   static reload() {
      this.navigate(new URL(location.href), "replace");
   }

   /**
    * Registers a callback function to be executed when the specified event is triggered.
    *
    * @param {string} event - The name of the event to listen for.
    * @param {Function} callback - The function to call when the event is triggered.
    */
   static on(event, callback) {
      if (!this._events[event]) {
         this._events[event] = [];
      }
      this._events[event].push(callback);
   }

   /**
    * Emits an event, invoking all registered callbacks for the specified event.
    *
    * @param {string} event - The name of the event to emit.
    * @param {Object} payload - The data to pass to each callback function.
    */
   static emit(event, payload) {
      const callbacks = this._events[event] || [];

      for (const callback of callbacks) {
         if (typeof callback === "function") {
            callback(payload);
         }
      }
   }

   static setState(stateKey, value) {
      return new Promise(async (resolve, reject) => {
         let currentScroll = {
            top: scrollY,
            left: scrollX,
         };

         const url = new URL(location.href);
         phpspa.emit("beforeload", { route: url });

         const response = await fetch(url, {
            method: "PHPSPA_GET",
            body: JSON.stringify({ stateKey, value }),
            mode: "same-origin",
            keepalive: true,
         });

         response
            .text()
            .then((res) => {
               try {
                  let json = JSON.parse(res);
                  resolve();
                  call(json);
               } catch (e) {
                  let data = res ?? "";
                  reject(e);
                  call(data);
               }
            })
            .catch((e) => {
               reject(e);
            });

         function call(data) {
            if (
               "string" === typeof data?.title ||
               "number" === typeof data?.title
            ) {
               document.title = data.title;
            }

            let targetElement =
               document.getElementById(data?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            targetElement.innerHTML = data?.content ?? data;

            const stateData = {
               url: url?.href ?? url,
               title: data?.title ?? document.title,
               targetID: data?.targetID ?? targetElement.id,
               content: data?.content ?? data,
            };

            history.replaceState(stateData, stateData.title, url);

            scroll(currentScroll);

            runInlineStyles(targetElement);
            runInlineScripts(targetElement);
         }
      });
   }
}

(function () {
   if (typeof window.phpspa === "undefined") {
      window.phpspa = phpspa;
   }
})();

function runInlineScripts(container) {
   const scripts = container.querySelectorAll(
      "script[data-type='phpspa/script']"
   );

   scripts.forEach((script) => {
      const newScript = document.createElement("script");
      newScript.textContent = `(function() {\n${script.textContent}\n})();`;
      document.head.appendChild(newScript).remove();
   });
}

function runInlineStyles(container) {
   const styles = container.querySelectorAll("style[data-type='phpspa/css']");

   styles.forEach((style) => {
      const newStyle = document.createElement("style");
      newStyle.textContent = style.textContent;
      document.head.appendChild(newStyle).remove();
   });
}
