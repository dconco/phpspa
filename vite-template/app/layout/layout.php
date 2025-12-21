<?php

require_once 'app/components/svgs/MenuIcon.php';

$layout = function() use (&$app) {

   $html = file_get_contents('index.html');

   // --- Check if Vite dev server is running ---
   // $viteRunning = useFetch('http://localhost:5173')->timeout(1)->get()->text();

   $viteRunning = strpos($html, '@vite/client') !== false;

   if (!$viteRunning) {
      // --- Production: Load assets from manifest ---

      $manifest = json_decode(file_get_contents('public/assets/.vite/manifest.json'), true);

      $mainEntry = $manifest['src/main.ts'];

      // --- Add CSS link tags for all imported stylesheets to the Application ---

      if (isset($mainEntry['css'])) {
         foreach ($mainEntry['css'] as $cssFile) {
            $app?->styleSheet(content: "/assets/$cssFile", type: 'text/css');
         }
      }

      // --- Add Production Javascript tags for the main Application script ---

      $app?->script(content: '/assets/' . $mainEntry['file'], type: 'module');
   }

   return $html;
};
