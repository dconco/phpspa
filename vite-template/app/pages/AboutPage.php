<?php

use PhpSPA\Component;

require_once 'app/components/AboutComponents.php';

$aboutPage = new Component(fn() => <<<HTML
   <div class="mx-auto max-w-5xl space-y-16 text-left">
      <AboutComponents::Intro />
      <AboutComponents::Triptych />

      <section class="grid gap-8 lg:grid-cols-3">
         <AboutComponents::Seo />
         <AboutComponents::Preloading />
         <AboutComponents::NativePerformance />
      </section>

      <AboutComponents::FinalCta />
      <AboutComponents::HiddenSeoSummary />
   </div>
HTML);

$aboutPage
   ->route('/about')
   ->title('About PhpSPA — Runtime, tooling, and integration details')
   ->meta(name: 'description', content: "Understand PhpSPA runtime lifecycle, integration options, SEO-friendly hydration, and developer tooling stack.")
   ->meta(name: 'keywords', content: 'PhpSPA runtime, PHP routing, hydration, CSRF protection')
   ->meta(property: 'og:title', content: 'About PhpSPA — Runtime, tooling, and integration details')
   ->meta(property: 'og:description', content: "Dive into PhpSPA approach to routing, preloading, and multi-framework integration while keeping PHP templates expressive.");
