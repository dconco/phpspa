<?php

return fn () => <<<HTML
   <html>
      <head>
         <title>PHP SPA PROJECT BY DCONCO</title>

         <style>
            #hashID {
               padding-top: 100vh;
               padding-bottom: 100vh;
            }
         </style>
      </head>
      <body>
         <div id=app>
            __CONTENT__
         </div>

         <!-- phpSPA JS PLUGIN -->
         <script type=application/javascript src=../src/phpspa.js></script>
      </body>
   </html>
HTML;