<?php

return fn () => <<<HTML
   <html>
      <head>
         <title>PHP SPA PROJECT BY DCONCO</title>
      </head>
      <body>
         <div id="app">
            __CONTENT__
         </div>

         <!-- phpSPA JS PLUGIN -->
         <script src="../src/index.js"></script>
         <!-- <script src="https://cdn.jsdelivr.net/npm/phpspa-js"></script> -->

         <script>
            phpspa.on("beforeload", ({ route }) => {
               document.getElementById("app").innerHTML = "<h1>Loading...</h1>";
               console.log("Before Load: " + route);
            });

            phpspa.on("load", ({ route, success, error }) => {
               console.log("Loaded!");

               if (!success) {
                  console.log('But an error occured: ', error);
               }
            });
         </script>
      </body>
   </html>
HTML;