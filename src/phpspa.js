(function () {
   let info = document.querySelectorAll('a[data-type="phpspa-link-tag');

   info.forEach((element) => {
      element.addEventListener("click", async (ev) => {
         ev.preventDefault();
         let url = new URL(element.href);
         phpspa.navigate(url);
      });
   });
})();

class phpspa {
   static states = {};

   static navigate(url, state = "push") {
      (async () => {
         let initialPath = location.pathname;
         if (state === "push") {
            history.pushState(url.pathname, url.pathname, url);
         } else if (state === "replace") {
            history.replaceState(url.pathname, url.pathname, url);
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
      targetElement.innerHTML = content;

      let url = new URL(
         Object.keys(this.states).at(
            Object.keys(this.states).indexOf(history.state) - 1
         ),
         location.href
      );
      let hashedElement = document.getElementById(url.hash.substring(1));

      if (hashedElement) {
         scroll({
            top: hashedElement.offsetTop,
         });
      }
   }

   static forward() {
      history.back();

      let [targetElement, content] =
         this.states[
            Object.keys(this.states).at(
               Object.keys(this.states).indexOf(history.state) + 1
            )
         ];
      targetElement.innerHTML = content;

      let url = new URL(
         Object.keys(this.states).at(
            Object.keys(this.states).indexOf(history.state) + 1
         ),
         location.href
      );
      let hashedElement = document.getElementById(url.hash.substring(1));

      if (hashedElement) {
         scroll({
            top: hashedElement.offsetTop,
         });
      }
   }
}
