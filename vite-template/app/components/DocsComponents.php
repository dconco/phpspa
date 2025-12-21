<?php

class DocsComponents {

   public function Hero(): string
   {
      return <<<HTML
         <section class="card">
            <p class="pill">Client runtime</p>
            <h1 class="mt-6 text-4xl font-semibold text-white">All of @dconco/phpspa in one place.</h1>
            <p class="mt-4 text-lg text-slate-300">
               These docs focus on the Vite + TypeScript runtime that boots when you import <code class="code-chip">@dconco/phpspa</code>. Every API here complements
               the PHP-first pages inside this starter.
            </p>
            <div class="mt-8 grid gap-6 md:grid-cols-3 text-sm text-slate-200">
               <div>
                  <p class="text-xs uppercase tracking-[0.4em] text-indigo-200">Lifecycle</p>
                  <p class="mt-2">Listen to <code class="code-chip">beforeload</code>/<code class="code-chip">load</code> and hook analytics.</p>
               </div>
               <div>
                  <p class="text-xs uppercase tracking-[0.4em] text-teal-200">Navigation</p>
                  <p class="mt-2">Drive <code class="code-chip">navigate()</code>, history, reloads, and link interception.</p>
               </div>
               <div>
                  <p class="text-xs uppercase tracking-[0.4em] text-rose-200">State & effects</p>
                  <p class="mt-2">Share stores with <code class="code-chip">setState</code>, <code class="code-chip">useEffect</code>, and server callbacks.</p>
               </div>
            </div>
         </section>
      HTML;
   }



   public function Install(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-white/5 p-8">
            <div class="grid gap-6 lg:grid-cols-2">
               <div>
                  <p class="text-xs uppercase tracking-[0.4em] text-sky-200">Install</p>
                  <h2 class="mt-3 text-2xl font-semibold text-white">Bring the runtime into Vite.</h2>
                  <p class="mt-3 text-slate-300">The package depends on Vite (or any bundler) and executes automatically once imported. It registers itself on
                     <code class="code-chip">window.phpspa</code> so you can inspect state during development.</p>
               </div>
               <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 overflow-x-auto">
                  <pre class="text-xs text-slate-200">pnpm add @dconco/phpspa | npm install @dconco/phpspa

// src/main.ts
import phpspa from '@dconco/phpspa';
import './style.css';

phpspa.on('load', ({ route, success }) => {
   console.debug('[phpspa]', route, success);
});</pre>
               </div>
            </div>
         </section>
HTML;
   }



   public function Lifecycle(): string
   {
      return <<<HTML
         <section class="lg:grid gap-8 lg:grid-cols-2 flex flex-col">
            <article class="card overflow-x-auto space-y-3">
               <p class="text-xs uppercase tracking-[0.4em] text-amber-200">Events</p>
               <h3 class="mt-4 text-2xl font-semibold text-white">Hook into navigation.</h3>
               <p class="mt-3 text-slate-300">Both events replay the last payload for late listeners.</p>
               <ul class="mt-6 space-y-3 text-sm text-slate-200">
                  <li><span class="font-semibold text-white">beforeload</span> — fires before fetch. Show loaders, pause video, cancel requests.</li>
                  <li><span class="font-semibold text-white">load</span> — fires after DOM diff. Inspect <code class="code-chip">success</code>, <code class="code-chip">error</code>, <code class="code-chip">data</code>.</li>
               </ul>
            </article>
            <article class="card">
               <p class="text-xs uppercase tracking-[0.4em] text-emerald-200">Typical handler</p>
               <pre class="mt-4 rounded-2xl border border-white/10 bg-slate-950/60 p-5 text-xs text-slate-200 overflow-x-auto">const spinner = document.querySelector('#loader');

phpspa.on('beforeload', ({ route }) => {
   spinner?.classList.remove('hidden');
   console.time(route);
});

phpspa.on('load', ({ route, success, error }) => {
   spinner?.classList.add('hidden');
   console.timeEnd(route);
   if (!success) toast.error(error ?? 'Unknown error');
});</pre>
            </article>
         </section>
HTML;
   }



   public function Navigation(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-white/5 p-8">
            <p class="text-xs uppercase tracking-[0.4em] text-purple-200">Navigation helpers</p>
            <div class="mt-6 grid gap-6 md:grid-cols-2">
               <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                  <p class="text-sm font-semibold text-white">Programmatic control</p>
                  <ul class="mt-4 space-y-2 text-sm text-slate-200">
                     <li><code class="code-chip">phpspa.navigate(url, 'push'|'replace')</code></li>
                     <li><code class="code-chip">phpspa.back()</code> / <code class="code-chip">phpspa.forward()</code></li>
                     <li><code class="code-chip">phpspa.reload()</code> — soft refresh current URL</li>
                     <li><code class="code-chip">phpspa.reloadComponent()</code> — refresh the active target only</li>
                  </ul>
               </div>
               <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
                  <p class="text-sm font-semibold text-white">Declarative links</p>
                  <p class="mt-3 text-slate-300">Anchor tags emitted by <code class="code-chip">&lt;Component.Link&gt;</code> include
                     <code class="code-chip">data-type="phpspa-link-tag"</code>. The runtime intercepts same-origin clicks, keeps scroll restoration, and falls back for
                     <code class="code-chip">target="_blank"</code> or downloads.</p>
                  <p class="mt-4 text-sm text-slate-300">Add the data attribute manually to any custom anchor if you want the same behavior.</p>
               </div>
            </div>
         </section>
      HTML;
   }



   public function State(): string
   {
      return <<<'HTML'
         <section class="space-y-8">
            <div class="grid gap-8 lg:grid-cols-2 min-w-0">
               <article class="card min-w-0 overflow-x-auto w-full max-w-full">
                  <p class="text-xs uppercase tracking-[0.4em] text-cyan-200">State bridge</p>
                  <p class="mt-4 text-slate-300">Named imports expose the same helpers as the global runtime.</p>
                  <pre class="mt-6 rounded-2xl border border-white/10 bg-slate-950/60 p-5 text-xs text-slate-200 overflow-x-auto">import { setState, useEffect } from '@dconco/phpspa';

setState('counter', (prev: number) => prev + 1)
   .then(() => console.log('DOM updated!'));

useEffect(() => {
   const timer = setInterval(() => setState('ping', Date.now()), 4000);
   return () => clearInterval(timer);
}, ['ping']);
</pre>
               </article>
               <article class="card">
                  <p class="text-xs uppercase tracking-[0.4em] text-lime-200">Rules of thumb</p>
                  <ul class="mt-4 space-y-3 text-sm text-slate-200">
                     <li>Effects run once on mount, then whenever one of the dependency keys is touched by <code class="code-chip">setState</code>.</li>
                     <li><code class="code-chip">setState</code> replays the current routes with a signed payload so PHP can re-render each target.</li>
                     <li>Cleanup functions run before the next effect invocation and during navigation.</li>
                     <li>Global helpers also live on <code class="code-chip">window.setState</code> and <code class="code-chip">window.useEffect</code> for quick demos.</li>
                  </ul>
                  <p class="mt-6 text-sm text-slate-300">If the DOM you touch lives inside a PhpSPA target, it gets replaced on navigation, so your effect unmounts. Re-register your helper after each
                     <code class="code-chip">load</code> event or move the logic into the static layout.</p>
               </article>
            </div>
            <div class="grid gap-8 lg:grid-cols-2 min-w-0">

               <DocsComponents::StableCallbacks />
               
               <DocsComponents::TypescriptHelpers />

            </div>
         </section>
HTML;
   }



   public function ServerCalls(): string
   {
      return <<<HTML
         <section class="rounded-4xl border border-white/10 bg-white/5 p-8">
            <div class="grid gap-6 lg:grid-cols-2">
               <div class="card min-w-0 overflow-x-auto wrap-anywhere">
                  <p class="text-xs uppercase tracking-[0.4em] text-rose-200">Server calls</p>
                  <h3 class="mt-4 text-2xl font-semibold text-white">Use <code class="code-chip">__call</code> with <code class="code-chip">useFunction</code>.</h3>
                  <p class="mt-3 text-slate-300">PhpSPA emits signed tokens for each callable you expose from PHP. Grab them server-side via
                     <code class="code-chip">Component\useFunction('function_name')->token</code>, pass the token plus any arguments to
                     <code class="code-chip">__call</code>, and the helper handles encoding, Authorization headers, and JSON decoding.</p>
               </div>
               <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 overflow-x-auto">
<pre class="text-xs text-slate-200">import { __call } from '@dconco/phpspa';

async function toggle(id: string) {
   const result = await __call(window.token, id);
   if (result?.error) throw new Error(result.error);
}

// --- PHP Side ---
&lt;?php
function Toggle(\$id) { ... }

\$token = Component\useFunction('Toggle')->token;

\$app = new PhpSPA\App();

\$app->script(fn () => &lt;&lt;&lt;JS
   window.token = \$token;
JS);
</pre>
               </div>
            </div>
         </section>
HTML;
   }



   public function Assets(): string
   {
      return <<<HTML
         <section class="grid gap-8 lg:grid-cols-2">
            <article class="card">
               <p class="text-xs uppercase tracking-[0.4em] text-purple-200">Scripts & styles</p>
               <ul class="mt-4 space-y-3 text-sm text-slate-200">
                  <li>Inline <code class="code-chip">&lt;script&gt;</code> blocks rerun in isolated scopes after each navigation.</li>
                  <li><code class="code-chip">&lt;phpspa-script&gt;</code> (or <code class="code-chip">script[data-type="phpspa/script"]</code>) fetch once, cache contents, and respect CSP nonces.</li>
                  <li>Inline <code class="code-chip">&lt;style&gt;</code> tags dedupe via content hash before re-injection.</li>
               </ul>
            </article>
            <article class="card">
               <p class="text-xs uppercase tracking-[0.4em] text-amber-200">Debug tips</p>
               <ul class="mt-4 space-y-3 text-sm text-slate-200">
                  <li>Pair <code class="code-chip">beforeload</code>/<code class="code-chip">load</code> with analytics to measure swap latency.</li>
                  <li>Use <code class="code-chip">setState(...).then()</code> to wait for DOM updates before reading measurements.</li>
                  <li>Log <code class="code-chip">beforeload</code>/<code class="code-chip">load</code> payloads directly from the event bus to inspect routes without exposing globals.</li>
               </ul>
            </article>
         </section>
      HTML;
   }



   public function StableCallbacks(): string {
      return <<<HTML
         <article class="card min-w-0 overflow-x-auto w-full max-w-full">
            <p class="text-xs uppercase tracking-[0.4em] text-emerald-200">Stable callbacks</p>
            <p class="mt-4 text-slate-300"><code class="code-chip">useCallback(fn, deps)</code> returns a memoized version of fn whose identity stays stable across renders until any dependency changes. Use it when attaching event listeners, passing callbacks to child components, or anytime a stable reference prevents duplicate effects</p>
            <pre class="mt-4 rounded-2xl border border-white/10 bg-slate-950/60 p-5 text-xs text-slate-200 overflow-x-auto min-w-0 max-w-full">import phpspa, { useCallback } from '@dconco/phpspa';

const handleToggle = useCallback((event: MouseEvent) => {
   event.preventDefault();
   document.querySelector('#nav-links')?.classList.toggle('open');
}, []);

phpspa.on('load', ({ success }) => {
   if (!success) return;
   document.querySelector('#nav-trigger')?.addEventListener('click', handleToggle, { passive: true });
});
</pre>
         </article>
HTML;
   }


   public function TypescriptHelpers(): string {
      return <<<'HTML'
         <article class="card min-w-0">
            <p class="text-xs uppercase tracking-[0.4em] text-purple-200">TypeScript helpers</p>
            <p class="mt-4 text-slate-300">Import runtime types whenever you need strongly typed payloads, caches, or instance helpers.</p>
            <pre class="mt-4 rounded-2xl border border-white/10 bg-slate-950/60 p-5 text-xs text-slate-200 overflow-x-auto min-w-0 max-w-full">import type { EventPayload, EventName, StateValueType, PhpSPAInstance } from '@dconco/phpspa';

declare const phpspaInstance: PhpSPAInstance;

const cache: Record&lt;string, StateValueType> = {};

phpspaInstance.on('load', ({ route, success }: EventPayload) => {
   const eventName: EventName = 'load';

   cache[eventName] = { route, success } satisfies StateValueType;
   console.log(`[${eventName}] ${route}`, success);
});
</pre>
      </article>
HTML;
   }
}
