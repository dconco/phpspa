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
         runInlineScripts(targetElement);
      } else {
         phpspa.navigate(new URL(location.href), "replace");
      }

      history.scrollRestoration = "auto";
   });
})();

class phpspa {
   // static states = {};
   static callback = [];

   static navigate(url, state = "push") {
      (async () => {
         // let initialPath = location.pathname;

         const response = await fetch(url, {
            method: "PHPSPA_GET",
            mode: "same-origin",
            keepalive: true,
         });

         response.text().then((res) => {
            try {
               let json = JSON.parse(res);
               call(json);
            } catch (e) {
               let data = res ?? "";
               call(data);
            }
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
               });
            }

            runInlineScripts(targetElement);
         }
      })();
   }

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

   static reload() {
      this.navigate(new URL(location.href), "replace");
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
      newScript.textContent = script.textContent;
      document.head.appendChild(newScript).remove();
   });
}
