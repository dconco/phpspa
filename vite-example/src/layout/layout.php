<?php

$layout = function() {

   $html = file_get_contents('frontend/index.html');

   // --- Check if Vite dev server is running ---
   $viteRunning = @file_get_contents('http://localhost:5173') !== false;

   if (!$viteRunning) {
      // --- Production: Load assets from manifest ---

      $manifest = json_decode(file_get_contents('public/assets/.vite/manifest.json'), true);

      $mainEntry = $manifest['src/main.js'];
      $cssLinks = '';

      // --- Build CSS link tags for all imported stylesheets ---

      if (!empty($mainEntry['css'])) {
         foreach ($mainEntry['css'] as $cssFile) {
            $cssLinks .= '<link rel="stylesheet" href="/public/assets/' . $cssFile . '">' . PHP_EOL;
         }
      }

      // --- Replace dev main.js with production version ---

      $html = str_replace(
         [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            '<script type="module" src="http://localhost:5173/src/main.js"></script>'
         ],
         [
            $cssLinks,
            '<script type="module" src="/public/assets/' . $mainEntry['file'] . '"></script>'
         ],
         $html
      );
   }

   return $html;
};
