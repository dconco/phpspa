<?php

use PhpSPA\Component;
use PhpSPA\Http\Request;

require_once '../../vendor/autoload.php';

$layout = fn (Request $req) => <<<HTML
   <!DOCTYPE html>
   <html lang="en">

   <head>
      <title>API</title>
   </head>

   <body>
      <div>
         <h2>API Index Page</h2>
         <h4>{$req->getUri()}</h4>
         <Component.Link>yo</Component.Link>
      </div>
   </body>

   </html>
HTML;

echo Component::Render($layout);