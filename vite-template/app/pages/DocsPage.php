<?php

use PhpSPA\Component;

require_once 'app/components/DocsComponents.php';

$docsPage = new Component(fn() => <<<HTML
   <div class="mx-auto max-w-5xl space-y-16 text-left">
      <DocsComponents::Hero />
      <DocsComponents::Install />
      <DocsComponents::Lifecycle />
      <DocsComponents::Navigation />
      <DocsComponents::State />
      <DocsComponents::ServerCalls />
      <DocsComponents::Assets />
   </div>
HTML);

$docsPage
   ->route('/client-runtime')
   ->title('Client Runtime — PhpSPA + Vite + TypeScript')
   ->meta(name: 'description', content: 'Complete reference for the @dconco/phpspa client runtime: lifecycle events, navigation helpers, state bridge, __call, and asset execution.')
   ->meta(name: 'keywords', content: 'PhpSPA runtime, phpspa.on, setState, useEffect, __call, Component.Link')
   ->meta(property: 'og:title', content: 'Client Runtime — PhpSPA + Vite + TypeScript')
   ->meta(property: 'og:description', content: 'Learn every client API exposed by @dconco/phpspa when paired with Vite and TypeScript.');
