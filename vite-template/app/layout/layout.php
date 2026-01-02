<?php

use function Component\useFetch;

require_once 'app/components/svgs/MenuIcon.php';
require_once 'app/components/HeaderComponent.php';

$layout = function() use (&$app) {

   // --- Vite dev server origin ---
   // NOTE: If you change your Vite host/port, update this AND the two dev <script> tags in index.html
   $viteDevOrigin = 'http://localhost:5173';

   $isProduction = getenv('APP_ENV') === 'production';

   // --- Check if Vite dev server is running ---
   $viteLayout = $isProduction ? false : useFetch($viteDevOrigin)->timeout(1)->get()->text();

   if (!$viteLayout) {
      $viteLayout = file_get_contents('index.html');

      // --- Production: Load assets from manifest ---

      $manifest = json_decode(file_get_contents('public/assets/.vite/manifest.json'), true);

      $mainEntry = $manifest['src/main.ts'];

      // --- Add CSS link tags for all imported stylesheets to the Application ---

      foreach ($mainEntry['css'] ?? [] as $cssFile) {
         $app?->styleSheet(content: "/assets/$cssFile", type: 'text/css');
      }

      // --- Add Production Javascript tags for the main Application script ---

      $app?->script(content: '/assets/' . $mainEntry['file'], type: 'module');

      // --- Remove dev urls from layout ---
      $viteLayout = str_replace(["$viteDevOrigin/@vite/client", "$viteDevOrigin/src/main.ts"], '', $viteLayout);
   }

   return $viteLayout;
};
