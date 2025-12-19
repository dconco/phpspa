<?php

use PhpSPA\Component;

require_once 'app/components/HomeComponents.php';

$homePage = new Component(fn() => <<<HTML
   <div class="mx-auto max-w-6xl space-y-20 text-left">
      <HomeComponents::Hero />
      <HomeComponents::FeatureDeck />
      <HomeComponents::Workflow />
      <HomeComponents::Insights />
      <HomeComponents::Cta />
   </div>
HTML);

$homePage
   ->route('/')
   ->title('PhpSPA Design System — Vite + Tailwind + PhpSPA')
   ->meta(name: 'description', content: 'Design-forward PhpSPA starter pairing PHP controllers with Vite, Tailwind, and typed state helpers for seamless SPA navigation.')
   ->meta(name: 'keywords', content: 'PhpSPA, PHP SPA, Tailwind, Vite, component framework')
   ->meta(property: 'og:title', content: 'PhpSPA Design System — Vite + Tailwind + PhpSPA')
   ->meta(property: 'og:description', content: "Explore PhpSPA component-driven PHP workflow, instant navigation, and production-ready Vite tooling.");
