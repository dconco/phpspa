<?php

function Layout (): string
{
   return <<<HTML
      <html>
         <head>
            <title>PHP SPA PROJECT BY DCONCO</title>
         </head>
         <body>
            <div id="app"></div>

            <!-- phpSPA JS PLUGIN -->
            <script type="application/javascript" src="/phpspa.min.js"></script>
         </body>
      </html>
   HTML;
}