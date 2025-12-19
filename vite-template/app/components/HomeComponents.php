<?php

class HomeComponents {

   public function Hero(): string
   {
      return <<<HTML
         <section class="relative overflow-hidden rounded-4xl border border-white/10 bg-linear-to-br from-white/10 via-white/5 to-transparent p-10 shadow-[0_0_120px_rgba(59,130,246,0.25)]">
            <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
               <div>
                  <p class="pill">Full-stack SPA flow</p>
                  <h1 class="mt-6 text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-6xl">
                     Design once. Hydrate everywhere with PhpSPA.
                  </h1>
                  <p class="mt-6 max-w-2xl text-lg text-slate-300">
                     Craft dynamic PHP-driven interfaces while letting Vite compile your components, Tailwind style them,
                     and PhpSPA orchestrate seamless transitions, state hydration, and CSRF-safe requests.
                  </p>
                  <div class="mt-10 flex flex-wrap gap-4">
                     <Component.Link to="/about" class="inline-flex items-center gap-3 rounded-full bg-white px-6 py-3 text-sm font-semibold tracking-wide text-slate-900 transition hover:bg-slate-100">
                        Explore Docs
                        <span aria-hidden>&rarr;</span>
                     </Component.Link>
                     <a href="https://github.com/dconco/phpspa" target="_blank" rel="noreferrer" class="inline-flex items-center gap-3 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold tracking-wide text-white transition hover:border-white">
                        View Source
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M5 15L15 5M15 5H6M15 5V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                     </a>
                  </div>
                  <dl class="mt-10 grid gap-6 text-sm text-slate-300 sm:grid-cols-3">
                        <div>
                           <dt class="uppercase tracking-[0.3em] text-white/60">Instant reload</dt>
                           <dd class="mt-2 text-2xl font-semibold text-white">Vite dev + PhpSPA</dd>
                        </div>
                        <div>
                           <dt class="uppercase tracking-[0.3em] text-white/60">State sharing</dt>
                           <dd class="mt-2 text-2xl font-semibold text-white">Stores & Hooks</dd>
                        </div>
                        <div>
                           <dt class="uppercase tracking-[0.3em] text-white/60">Secure forms</dt>
                           <dd class="mt-2 text-2xl font-semibold text-white">Built-in CSRF</dd>
                        </div>
                  </dl>
               </div>
               <div class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl">
                  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.35),transparent_60%)]"></div>
                  <div class="relative space-y-6">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 overflow-x-auto">
                           <p class="text-xs uppercase tracking-[0.4em] text-indigo-300">Component props</p>
                           <pre class="mt-4 text-xs text-slate-200">&lt;?php
class Person {
   public function __construct(
      public readonly string \$name,
      public readonly string \$role
   ) {}
}

function Profile(Person \$person) {
   return &lt;&lt;&lt;HTML
      &lt;article>
         &lt;h3>{\$person->name}&lt;/h3>
         &lt;p>{\$person->role}&lt;/p>
      &lt;/article>
   &#8203;HTML;
}

\$p = new Person('Dave', 'Admin'); fmt(\$p);

echo &lt;&lt;&lt;HTML
   &lt;Profile person="{\$p}" />
&#8203;HTML;
</pre>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                           <p class="text-xs uppercase tracking-[0.4em] text-emerald-300">PHP state hooks</p>
                           <pre class="mt-4 text-xs text-slate-200">&lt;?php
use function Component\useState;
use function Component\useEffect;

\$counter = useState('counter', 0);

useEffect(function () use (\$counter) {
   \$counter(\$counter() + 1);
}, [ \$counter ]);
</pre>
               </div>
         </div>
      </div>
   </div>
</section>
HTML;
   }



   public function FeatureDeck(): string
   {
      return <<<HTML
         <section class="grid gap-8 lg:grid-cols-3">
            <article class="card">
               <p class="text-sm font-semibold uppercase tracking-[0.4em] text-indigo-200">Component Router</p>
               <h3 class="mt-4 text-2xl font-semibold text-white">Nested layouts & target slots</h3>
               <p class="mt-3 text-slate-300">Compose layouts with multiple target areas, lazy load pages, and stream updates without rebuilding your PHP routes.</p>
               <ul class="mt-6 space-y-2 text-sm text-slate-200">
                  <li>•&nbsp;Route patterns with typed params</li>
                  <li>•&nbsp;Error + loading fallbacks baked in</li>
                  <li>•&nbsp;Automatic title + metadata sync</li>
               </ul>
            </article>
            <article class="card">
               <p class="text-sm font-semibold uppercase tracking-[0.4em] text-emerald-200">State & Events</p>
               <h3 class="mt-4 text-2xl font-semibold text-white">Share state across PHP & JS</h3>
               <p class="mt-3 text-slate-300">Tap into the PhpSPA store bridge to sync props, emit lifecycle events, and respond to transitions on both sides of the stack.</p>
               <ul class="mt-6 space-y-2 text-sm text-slate-200">
                  <li>•&nbsp;<code class="code-chip">useState()</code>/<code class="code-chip">useEffect()</code> helpers shared in PHP components</li>
                  <li>•&nbsp;<code class="code-chip">phpspa.on('beforeload'|'load')</code> for runtime events</li>
                  <li>•&nbsp;Session + setState bridge keeps view data alive</li>
               </ul>
            </article>
            <article class="card">
               <p class="text-sm font-semibold uppercase tracking-[0.4em] text-sky-200">HTTP Bridge</p>
               <h3 class="mt-4 text-2xl font-semibold text-white">Secure requests without reloads</h3>
               <p class="mt-3 text-slate-300">Make CSRF-safe form posts, stream JSON payloads, and tap into PhpSPA's request middleware without touching Axios.</p>
               <ul class="mt-6 space-y-2 text-sm text-slate-200">
                  <li>•&nbsp;Built-in Request + Response helpers</li>
                  <li>•&nbsp;Automatic compression + caching</li>
                  <li>•&nbsp;Fine-grained auth guards per route</li>
               </ul>
            </article>
         </section>
      HTML;
   }




   public function Workflow(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-white/5 p-10">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
               <div class="max-w-2xl">
                  <p class="text-xs uppercase tracking-[0.4em] text-teal-200">Workflow</p>
                  <h2 class="mt-4 text-3xl font-semibold text-white">Stretch PhpSPA across the entire experience</h2>
                  <p class="mt-4 text-slate-300">Every step is powered by PhpSPA primitives. You write PHP structures, drop in Component targets, and let the runtime orchestrate hydration.</p>
               </div>
               <div class="grid gap-4 sm:grid-cols-2 lg:w-1/2">
                  <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                        <p class="text-sm font-semibold text-white">01 · Blueprint layouts</p>
                        <p class="mt-2 text-sm text-slate-300">Slot content into named targets, define modals, fallback loaders, and page metadata directly in PHP.</p>
                  </div>
                  <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                        <p class="text-sm font-semibold text-white">02 · Bind stores</p>
                        <p class="mt-2 text-sm text-slate-300">Bind useState hooks to client setState calls, subscribe to route events, and hydrate components without extra glue code.</p>
                  </div>
                  <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                        <p class="text-sm font-semibold text-white">03 · Navigate instantly</p>
                        <p class="mt-2 text-sm text-slate-300">Use Component.Link or JS navigation helpers for view swaps, preserve scroll, and animate transitions per route.</p>
                  </div>
                  <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5">
                        <p class="text-sm font-semibold text-white">04 · Ship confidently</p>
                        <p class="mt-2 text-sm text-slate-300">Vite handles bundling, PhpSPA optimizes responses, and your PHP controllers remain lean.</p>
                  </div>
               </div>
            </div>
         </section>
      HTML;
   }



    
   public function Insights(): string
   {
      return <<<HTML
         <section class="grid gap-8 lg:grid-cols-[1fr_1fr]">
            <div class="card">
               <p class="text-xs uppercase tracking-[0.4em] text-purple-200">Telemetry</p>
               <h3 class="mt-4 text-3xl font-semibold text-white">Production-ready signals</h3>
               <p class="mt-4 text-slate-300">Instrumentation hooks let you observe route swaps, latency, and payload sizes without bolt-on tooling.</p>
               <dl class="mt-8 grid gap-6 sm:grid-cols-3">
                     <div>
                        <dt class="text-sm text-white/70">Average swap</dt>
                        <dd class="mt-1 text-3xl font-semibold text-white">68 ms</dd>
                     </div>
                     <div>
                        <dt class="text-sm text-white/70">Bundle size</dt>
                        <dd class="mt-1 text-3xl font-semibold text-white">17 KB</dd>
                     </div>
                     <div>
                        <dt class="text-sm text-white/70">Hydration score</dt>
                        <dd class="mt-1 text-3xl font-semibold text-white">99%</dd>
                     </div>
               </dl>
            </div>
            <div class="card">
               <p class="text-xs uppercase tracking-[0.4em] text-rose-200">Quote</p>
               <p class="mt-6 text-xl text-white">
                     “PhpSPA bridges the comfort of PHP routing with the speed of SPA transitions. Paired with Vite and Tailwind, we iterate on real UI in minutes.
                     The runtime events, state maps, and request helpers mean zero glue code.”
               </p>
               <p class="mt-6 text-sm font-semibold text-white">– Studio Atlas Engineering</p>
            </div>
         </section>
      HTML;
   }
   


   
   public function Cta(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-linear-to-r from-indigo-500/80 via-sky-500/70 to-cyan-400/70 p-10 text-slate-900">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
               <div>
                     <p class="text-xs uppercase tracking-[0.4em] text-slate-900/70">Call to action</p>
                     <h2 class="mt-4 text-3xl font-semibold">Drop PhpSPA into your next PHP project.</h2>
                     <p class="mt-4 max-w-2xl text-base text-slate-900/80">Use the CLI to scaffold routes, configure Vite for dev + prod, and keep your favorite framework (Laravel, Symfony, CodeIgniter) in the loop.</p>
               </div>
               <div class="flex flex-wrap gap-4">
                     <Component.Link to="/about" class="inline-flex items-center gap-2 rounded-full border border-slate-900 px-5 py-3 text-sm font-semibold tracking-wide">
                        View integration guide
                        <span aria-hidden>&#x279C;</span>
                     </Component.Link>
                     <a href="https://www.npmjs.com/package/@dconco/phpspa" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold tracking-wide text-white">
                        npm install @dconco/phpspa
                     </a>
               </div>
            </div>
         </section>
      HTML;
   }
}
