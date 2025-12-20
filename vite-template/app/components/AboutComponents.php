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
                     <li>•&nbsp;&nbsp;<code class="code-chip">Component.Link</code>&nbsp;navigation & scroll preservation</li>
                     <li>•&nbsp;&nbsp;Lifecycle hooks (<code class="code-chip">beforeload</code>,&nbsp;<code class="code-chip">load</code>, custom)</li>
                     <li>•&nbsp;&nbsp;Request helper with CSRF + compression</li>
                     <li>•&nbsp;&nbsp;Layout targets, slot hydration, dynamic titles</li>
                  </ul>
               </div>
               <div>
                  <p class="text-sm uppercase tracking-[0.4em] text-teal-200">Tooling</p>
                  <ul class="mt-3 space-y-2 text-sm text-slate-200">
                     <li>•&nbsp;&nbsp;Vite dev server with instant HMR</li>
                     <li>•&nbsp;&nbsp;Tailwind v4 with layered tokens</li>
                     <li>•&nbsp;&nbsp;TypeScript helpers + store typings</li>
                     <li>•&nbsp;&nbsp;Production manifest auto-injection</li>
                  </ul>
               </div>
            </div>
         </section>
      HTML;
   }
   

   public function NativePerformance(): string
   {
      return <<<HTML
         <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
            <p class="text-xs uppercase tracking-[0.4em] text-emerald-200">Native performance</p>
            <p class="mt-4 text-slate-200">
               The PhpSPA runtime streams responses through the FFIBridge so minification and compression are handled by a C++ extension
               instead of PHP string operations. Bundles are squeezed by the native compressor, then gzipped or brotlied before the request
               leaves the server, keeping the templating layer expressive while responses remain lean.
            </p>
            <ul class="mt-6 space-y-2 text-sm text-slate-300">
               <li>•&nbsp;&nbsp;Native tokenizer trims whitespace + comments in ~15ms for 300KB payloads.</li>
               <li>•&nbsp;&nbsp;Streaming compressor emits pre-zipped HTML/JS so clients download 30–60% less.</li>
               <li>•&nbsp;&nbsp;Fallbacks detect missing extensions and transparently switch to PHP compressors.</li>
            </ul>
         </article>
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
                  <li>•&nbsp;&nbsp;Mount controllers then swap views inside <code class="code-chip">&lt;div id="app"&gt;</code>.</li>
                  <li>•&nbsp;&nbsp;Share auth context with Session or scoped hooks.</li>
                  <li>•&nbsp;&nbsp;Proxy API calls through PhpSPA&nbsp;<code class="code-chip">Http\Request</code>.</li>
               </ul>
            </article>
            <article class="rounded-3xl border border-white/10 bg-slate-950/60 p-6">
               <p class="text-xs uppercase tracking-[0.4em] text-rose-200">Deliverables</p>
               <ul class="mt-4 space-y-3 text-sm text-slate-200">
                  <li>•&nbsp;&nbsp;<code class="code-chip">pnpm dev</code>&nbsp;launches Vite + PHP for hot reload.</li>
                  <li>•&nbsp;&nbsp;<code class="code-chip">pnpm build</code>&nbsp;emits versioned assets + manifest.</li>
                  <li>•&nbsp;&nbsp;<code class="code-chip">app/layout/layout.php</code>&nbsp;swaps dev/prod automatically.</li>
                  <li>•&nbsp;&nbsp;Debug hooks log transitions for profiling.</li>
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
               <li>•&nbsp;&nbsp;Static-first payloads keep SEO bots happy.</li>
               <li>•&nbsp;&nbsp;Dynamic metadata comes from PHP before hydration.</li>
               <li>•&nbsp;&nbsp;Array routes let one component serve multiple URLs.</li>
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
            <pre class="mt-6 rounded-2xl border border-white/10 bg-slate-900/70 p-4 text-xs text-slate-200 overflow-x-auto">return (new Component(fn () => '&lt;Hero /&gt;'))
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
                     <Component.Link class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-sky-200 transition hover:border-white/50" to="https://phpspa.tech" target="_blank" rel="noreferrer">
                        Read full docs
                        <span aria-hidden>&#x2197;</span>
                     </Component.Link>
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



   public function HiddenSeoSummary(): string
   {
      return <<<HTML
         <section class="sr-only" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
            PhpSPA unifies PHP-first rendering with Vite tooling, Tailwind styling, typed state maps, and Component.Link navigation so
            controllers stay expressive while users enjoy SPA-class transitions. The runtime covers lifecycle hooks, CSRF-safe HTTP
            helpers, and layout slot hydration, letting teams compose multi-target pages without JavaScript glue. Typical projects author
            routes in app/pages, hydrate widgets from Vite’s src directory, bind stores with useState and useEffect, listen to phpspa.on
            events, then ship versioned assets through pnpm build while app/layout/layout.php wires dev and prod manifests.

            The platform embeds opinionated SEO support: every first request renders on the server with canonical titles, per-route
            metadata, and array-driven routes so a single component can power multiple URLs. Preloading APIs allow parents to name
            components, queue sibling micro-apps, and hydrate cached HTML instantly, avoiding waterfalls on content-rich layouts. Native
            performance comes from the FFIBridge C++ extension that tokenizes markup, performs whitespace/comment stripping in ~15ms for
            300KB payloads, and streams brotli or gzip output before the response leaves PHP, with automatic fallbacks when the extension
            is unavailable.

            Integration guidance highlights Laravel, Symfony, CodeIgniter, WordPress, or bespoke stacks: mount controllers, swap views
            beneath #app, share auth context, and proxy APIs through PhpSPA\Http\Request. Final calls-to-action reinforce the value
            proposition—single-language comfort, typed routes, hookable navigation, CSRF-safe forms, compression utilities, and IDE-ready
            hints—so teams grasp the entire PhpSPA runtime, tooling, and deployment story even if JavaScript is disabled.
         </section>
      HTML;
   }
}
