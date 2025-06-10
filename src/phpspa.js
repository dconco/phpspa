(function () {
   let info = document.querySelectorAll('a[data-type="phpspa-link-tag');

   info.forEach((element) => {
      element.addEventListener("click", async (ev) => {
         ev.preventDefault();
         phpspa.navigate(element.href);
      });
   });

   window.addEventListener("popstate", (ev) => {
      const state = ev.state;

      if (state && state.url && state.targetID && state.content) {
         let targetElement =
            document.getElementById(state.targetID) ?? document.body;

         phpspa.states[state.url.pathname] = [
            targetElement,
            targetElement.innerHTML,
         ];
         targetElement.innerHTML = state.content;

         if (state.scrollY) {
            scroll({
               top: state.scrollY,
            });
         }

         document.title = state.title || document.title;
      } else {
         phpspa.navigate(location.href, "replace");
      }
   });
})();

class phpspa {
   static states = {};

   static navigate(url, state = "push") {
      (async () => {
         let initialPath = location.pathname;
         let scrollY = window.scrollY;

         if (url instanceof URL === false) {
            url = new URL(url, location.href);
         }

         let response = await fetch(url, {
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
               document.getElementById(data?.targetID) ?? document.body;

            phpspa.states[initialPath] = [
               targetElement,
               targetElement.innerHTML,
            ];
            targetElement.innerHTML = data?.content ?? data;
            phpspa.states[url.pathname] = [
               targetElement,
               data?.content ?? data,
            ];

            const stateData = {
               url: url,
               title: data?.title ?? document.title,
               targetID: data?.targetID ?? targetElement.id,
               content: data?.content ?? data,
               scrollY: scrollY,
            };

            if (state === "push") {
               history.pushState(stateData, stateData.title, url);
            } else if (state === "replace") {
               history.replaceState(stateData, stateData.title, url);
            }

            let hashedElement = document.getElementById(url.hash?.substring(1));

            if (hashedElement) {
               scroll({
                  top: hashedElement.offsetTop,
               });
            }
         }
      })();
   }

   static back() {
      history.back();

      let [targetElement, content] =
         this.states[
            Object.keys(this.states).at(
               Object.keys(this.states).indexOf(history.state) - 1
            )
         ];

      let url = new URL(
         Object.keys(this.states).at(
            Object.keys(this.states).indexOf(history.state) - 1
         ),
         location.href
      );

      if (!targetElement) {
         this.navigate(url, "replace");
      } else {
         targetElement.innerHTML = content;
         let hashedElement = document.getElementById(url.hash.substring(1));

         if (hashedElement) {
            scroll({
               top: hashedElement.offsetTop,
            });
         }
      }
   }

   static forward() {
      history.forward();

      let [targetElement, content] =
         this.states[
            Object.keys(this.states).at(
               Object.keys(this.states).indexOf(history.state) + 1
            )
         ];

      let url = new URL(
         Object.keys(this.states).at(
            Object.keys(this.states).indexOf(history.state) + 1
         ),
         location.href
      );

      if (!targetElement) {
         this.navigate(url, "replace");
      } else {
         targetElement.innerHTML = content;
         let hashedElement = document.getElementById(url.hash.substring(1));

         if (hashedElement) {
            scroll({
               top: hashedElement.offsetTop,
            });
         }
      }
   }
}
