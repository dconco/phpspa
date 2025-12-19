<?php

class AboutComponents {

   public function Intro(): string
   {
      return <<<HTML
         <section class="card">
            <p class="pill">Inside PhpSPA</p>
            <h1 class="mt-6 text-4xl font-semibold text-white">Opinionated tooling without the heavy scaffolding.</h1>
            <p class="mt-4 text-lg text-slate-300">
               PhpSPA bundles routing, hydration, state, and HTTP utilities so your PHP controllers and templates stay expressive.
               This starter pairs it with Vite + Tailwind for fast feedback and a production-grade asset pipeline.
            </p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2">
               <div>
                  <p class="text-sm uppercase tracking-[0.4em] text-indigo-200">Runtime</p>
                  <ul class="mt-3 space-y-2 text-sm text-slate-200">
                     <li>•&nbsp;<code class="code-chip">Component.Link</code>&nbsp;navigation & scroll preservation</li>
                     <li>•&nbsp;Lifecycle hooks (<code class="code-chip">beforeload</code>, <code class="code-chip">load</code>, custom)</li>
                     <li>•&nbsp;Request helper with CSRF + compression</li>
                     <li>•&nbsp;Layout targets, slot hydration, dynamic titles</li>
                  </ul>
               </div>
               <div>
                  <p class="text-sm uppercase tracking-[0.4em] text-teal-200">Tooling</p>
                  <ul class="mt-3 space-y-2 text-sm text-slate-200">
                     <li>•&nbsp;Vite dev server with instant HMR</li>
                     <li>•&nbsp;Tailwind v4 with layered tokens</li>
                     <li>•&nbsp;TypeScript helpers + store typings</li>
                     <li>•&nbsp;Production manifest auto-injection</li>
                  </ul>
               </div>
            </div>
         </section>
      HTML;
   }
   



   public function Triptych(): string
   {
      return <<<HTML
         <section class="grid gap-8 lg:grid-cols-3">
            <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
               <p class="text-xs uppercase tracking-[0.4em] text-orange-200">Typical flow</p>
               <ol class="mt-4 space-y-3 text-sm text-slate-300">
                  <li><span class="font-semibold text-white">1.&nbsp;</span> Author layouts + routes in <code class="code-chip">app/pages/*</code>.</li>
                  <li><span class="font-semibold text-white">2.&nbsp;</span> Create interactive widgets in <code class="code-chip">src/</code> using Vite.</li>
                  <li><span class="font-semibold text-white">3.&nbsp;</span> Bind PHP data with <code class="code-chip">useState()</code> + <code class="code-chip">useEffect()</code>.</li>
                  <li><span class="font-semibold text-white">4.&nbsp;</span> Listen to route events via <code class="code-chip">phpspa.on()</code>.</li>
                  <li><span class="font-semibold text-white">5.&nbsp;</span> Deploy with <code class="code-chip">pnpm build</code> → manifest assets.</li>
               </ol>
            </article>
            <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
               <p class="text-xs uppercase tracking-[0.4em] text-sky-200">Integration</p>
               <p class="mt-4 text-slate-200">Use PhpSPA inside Laravel, Symfony, CodeIgniter, WordPress—or any custom framework.</p>
               <ul class="mt-6 space-y-2 text-sm text-slate-300">
                  <li>•&nbsp;Mount controllers then swap views inside <code class="code-chip">&lt;div id="app"&gt;</code>.</li>
                  <li>•&nbsp;Share auth context with Session or scoped hooks.</li>
                  <li>•&nbsp;Proxy API calls through PhpSPA <code class="code-chip">Http\Request</code>.</li>
               </ul>
            </article>
            <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
               <p class="text-xs uppercase tracking-[0.4em] text-rose-200">Deliverables</p>
               <ul class="mt-4 space-y-3 text-sm text-slate-200">
                  <li>•&nbsp;<code class="code-chip">pnpm dev</code> launches Vite + PHP for hot reload.</li>
                  <li>•&nbsp;<code class="code-chip">pnpm build</code> emits versioned assets + manifest.</li>
                  <li>•&nbsp;<code class="code-chip">app/layout/layout.php</code> swaps dev/prod automatically.</li>
                  <li>•&nbsp;Debug hooks log transitions for profiling.</li>
               </ul>
            </article>
         </section>
      HTML;
   }



      
   public function Seo(): string
   {
      return <<<HTML
         <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
            <p class="text-xs uppercase tracking-[0.4em] text-amber-200">SEO & multi-page</p>
            <p class="mt-4 text-slate-200">
               PhpSPA renders every first request through PHP, so crawlers receive fully formed HTML, titles, and meta tags.
               Define multiple entry points by passing arrays to <code class="code-chip">-&gt;route([...])</code>, scope per-route
               layouts with <code class="code-chip">-&gt;targetID()</code>, and emit canonical titles via <code class="code-chip">-&gt;title()</code>.
               Need a classic multi-page site? Simply add more components — each route can hydrate independently but still benefit
               from SPA navigations once the runtime boots.
            </p>
            <ul class="mt-6 space-y-2 text-sm text-slate-300">
               <li>•&nbsp;Static-first payloads keep SEO bots happy.</li>
               <li>•&nbsp;Dynamic metadata comes from PHP before hydration.</li>
               <li>•&nbsp;Array routes let one component serve multiple URLs.</li>
            </ul>
         </article>
      HTML;
   }




   public function Preloading(): string
   {
      return <<<HTML
         <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
            <p class="text-xs uppercase tracking-[0.4em] text-cyan-200">Preloading & composition</p>
            <p class="mt-4 text-slate-200">
                  Preload layout fragments or sibling apps so they are ready before PhpSPA swaps targets. Use
                  <code class="code-chip">-&gt;name()</code> to give each component a unique key, then chain <code class="code-chip">-&gt;preload('hero', 'sidebar')</code>
                  on the parent. PhpSPA runs those children on the server, caches their HTML, and hydrates them automatically when the main
                  component renders.
            </p>
            <pre class="mt-6 rounded-2xl border border-white/10 bg-slate-900/70 p-4 text-xs text-slate-200">return (new Component(fn () => '&lt;Hero /&gt;'))
         -&gt;name('landing')
         -&gt;preload('hero', 'stats')
         -&gt;route(['/landing', '/home'])
         -&gt;targetID('main')</pre>
            <p class="mt-4 text-sm text-slate-300">You can even preload entire micro-apps (dashboards, modals, counter demos) to avoid waterfalls when users land on a rich layout.</p>
         </article>
      HTML;
   }




   public function FinalCta(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-white/5 p-10">
            <div class="grid gap-8 md:grid-cols-2">
               <div>
                  <p class="text-xs uppercase tracking-[0.4em] text-lime-200">Why teams choose PhpSPA</p>
                  <h2 class="mt-4 text-3xl font-semibold text-white">Single language comfort, multi-surface reach.</h2>
                  <p class="mt-4 text-slate-300">Your designers stay in Tailwind, your backend stays in PHP, and PhpSPA binds them with declarative components, typed routes, and stateful transitions.</p>
                  <div class="mt-6 flex flex-wrap gap-4">
                     <Component.Link to="/" class="inline-flex items-center gap-2 rounded-full border border-white/30 px-5 py-3 text-sm font-semibold tracking-wide text-white">
                        Back to overview
                        <span aria-hidden>&#10548;</span>
                     </Component.Link>
                     <a class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-sky-200 transition hover:border-white/50" href="https://phpspa.tech" target="_blank" rel="noreferrer">
                        Read full docs
                        <span aria-hidden>&#x2197;</span>
                     </a>
                  </div>
               </div>
               <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
                  <p class="text-sm font-semibold text-white">Feature checklist</p>
                  <ul class="mt-4 space-y-3 text-sm text-slate-200">
                     <li>✅ Multi-target layouts</li>
                     <li>✅ Reactive store bridge</li>
                     <li>✅ Hookable navigation lifecycle</li>
                     <li>✅ CSRF-safe Request helper</li>
                     <li>✅ Compression + UTF-8 tools</li>
                     <li>✅ Type hinting + IDE helpers</li>
                  </ul>
               </div>
            </div>
         </section>
      HTML;
   }
}
