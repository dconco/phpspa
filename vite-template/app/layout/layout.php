<?php

$layout = function() use (&$app) {
   $html = file_get_contents('index.html');

   // --- Check if Vite dev server is running ---
   $viteRunning = @file_get_contents('http://localhost:5173') !== false;

   if (!$viteRunning) {
      // --- Production: Load assets from manifest ---

      $manifest = json_decode(file_get_contents('public/assets/.vite/manifest.json'), true);

      $mainEntry = $manifest['src/main.ts'];

      // --- Add CSS link tags for all imported stylesheets to the Application ---

      if (isset($mainEntry['css'])) {
         foreach ($mainEntry['css'] as $cssFile) {
            $app?->styleSheet("/assets/$cssFile");
         }
      }

      // --- Add CSS link tags for all imported stylesheets to the Application ---

      $app?->script('/assets/' . $mainEntry['file'], null, 'module');

      // --- Remove all dev script (eg., main.ts, @vite/client) in production ---

      $html = str_replace(
         [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            '<script type="module" src="http://localhost:5173/src/main.ts"></script>'
         ], '', $html, $count
      );
   }

   return $html;
};
