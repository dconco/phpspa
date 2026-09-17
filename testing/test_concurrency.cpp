// =============================================================================
// Concurrency / thread-safety characterization test
// (Task 4 of the windows-esbuild-timeout-fallback bugfix spec).
//
//   Property 2 - Preservation: concurrent compression is safe with a fixed
//   level (concurrent READS of the shared static currentLevel produce the same
//   per-input result as a single-threaded run).
//
// This test drives the two PROCESS-FREE public seams only:
//   - HtmlCompressor::minifyJS(js, scope)   -- the 2-arg native/internal
//                                              minifier (NO subprocess).
//   - HtmlCompressor::compress(html)         -- the full pipeline; inline
//                                              <script>/<style> are minified by
//                                              the native minifiers internally
//                                              (also NO subprocess).
// It deliberately does NOT call the 3-arg minifyJS overload, which invokes
// runBundler/esbuild (a subprocess) and would make the test non-deterministic
// and process-dependent. All inputs are SMALL, purely in-memory, and chosen so
// that no esbuild bundler path is ever reached.
//
// ---------------------------------------------------------------------------
// SHARED STATIC `HtmlCompressor::currentLevel` -- DATA-RACE NOTE
// ---------------------------------------------------------------------------
// `currentLevel` is process-global mutable state (a `static Level`). This test
// sets it EXACTLY ONCE at the start of each test case, BEFORE launching any
// thread, and never mutates it from within a thread. Concurrent *reads* of a
// fixed level are what this test exercises, and they are safe: the value is
// stable for the lifetime of the parallel region, so every thread observes the
// same level and every minifier read is a plain read of an unchanging value.
//
// Concurrent *writes* -- i.e. mixing compression levels across threads by
// reassigning currentLevel while other threads are running -- WOULD be a data
// race and is unsupported by the current design. That is intentionally OUT OF
// SCOPE for this cross-platform fix and is recorded as a potential follow-up
// finding. Do NOT add level-mutation-under-concurrency here.
//
// ---------------------------------------------------------------------------
// There is no local C++ toolchain; this suite is validated by the CI
// `cpp-tests` job (Linux + Windows) wired into
// .github/workflows/build-compressor.yml.
//
//   EXPECTED OUTCOME: PASSES on the UNFIXED code (baseline concurrent-read
//   safety with a fixed level).
//
//   _Requirements: 2.4 (native fallback always yields valid output),
//                  3.5 (internal minifier semantics preserved)_
//   Property annotation: **Validates: Requirements 2.4, 3.5 (Property 2 -
//   Preservation)**
// =============================================================================

#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

#include <atomic>
#include <string>
#include <thread>
#include <vector>

#include "compression/HtmlCompressor.h"

namespace {

   // Run the native (2-arg) minifier on a private copy and return the result.
   // Each caller passes its OWN std::string, so there is no shared mutable
   // input across threads.
   std::string minifyNative(const std::string& source, const std::string& scope) {
      std::string js = source; // private per-call copy
      HtmlCompressor::minifyJS(js, scope);
      return js;
   }

   // A native-minifier work item: an input plus the scope to minify it under.
   struct JsWork {
      std::string input;
      std::string scope; // "global" or "scoped"
   };

   // A compress() work item: an HTML snippet to run through the full pipeline.
   struct HtmlWork {
      std::string input;
   };

} // namespace

// -----------------------------------------------------------------------------
// Test A -- concurrent native minifyJS (2-arg).
//
// Precompute a single-threaded REFERENCE output for each independent input
// (mixing scope="global" and scope="scoped"). Then launch many threads, each
// running many iterations; every iteration minifies its OWN copy of an input
// and compares the result against the precomputed reference for that input.
// Any mismatch increments an atomic counter. After join, assert zero
// mismatches (and, implicitly, zero crashes -- a crash would fail the run).
//
// currentLevel is set ONCE here, before launching threads (see the data-race
// note at the top of the file), and is only READ concurrently thereafter.
// -----------------------------------------------------------------------------
TEST(concurrent_native_minify_matches_single_threaded_reference) {
   // Fixed level for the whole parallel region -- set ONCE, before threads.
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   // Independent inputs, a mix of global (no IIFE) and scoped (IIFE-wrapped).
   // These are the same shapes characterized in test_minify_js.cpp, so the
   // references below are consistent with the task-3 baseline outputs.
   const std::vector<JsWork> work = {
      {"// leading\nvar a=1;", "global"},        // line comment stripped
      {"a = 1\nb = 2\n", "global"},              // ASI: "a=1; b=2"
      {"return   x", "global"},                  // identifier-adjacency space
      {"var s = 'hello   world';", "global"},    // string literal preserved
      {"const x=1;\n", "scoped"},                // "(()=>{const x=1;})();"
      {"const x = 1;\nconsole.log(x);\n", "scoped"},
      {"let b = \"two  spaces\";", "scoped"},
      {"function f(){ return 'z  z'; }", "global"},
   };

   // Single-threaded references, computed before any thread starts.
   std::vector<std::string> reference;
   reference.reserve(work.size());
   for (const auto& w : work) {
      reference.push_back(minifyNative(w.input, w.scope));
   }

   // A large number of threads, each doing many iterations.
   const int kThreads = 32;
   const int kIterations = 300;

   std::atomic<long long> mismatches{0};
   std::atomic<long long> comparisons{0};

   std::vector<std::thread> threads;
   threads.reserve(kThreads);

   for (int t = 0; t < kThreads; ++t) {
      threads.emplace_back([&work, &reference, &mismatches, &comparisons]() {
         bool localMismatch = false;
         for (int iter = 0; iter < kIterations; ++iter) {
            for (std::size_t i = 0; i < work.size(); ++i) {
               // Each iteration operates on its OWN copy of the input.
               const std::string got = minifyNative(work[i].input, work[i].scope);
               comparisons.fetch_add(1, std::memory_order_relaxed);
               if (got != reference[i]) {
                  localMismatch = true;
               }
            }
         }
         if (localMismatch) {
            mismatches.fetch_add(1, std::memory_order_relaxed);
         }
      });
   }

   for (auto& th : threads) {
      th.join();
   }

   // Every concurrent minify must match its single-threaded reference exactly.
   CHECK_EQ(mismatches.load(), 0LL);
   // Sanity: we actually performed the expected amount of work.
   CHECK_EQ(comparisons.load(),
            static_cast<long long>(kThreads) * kIterations *
               static_cast<long long>(work.size()));
}

// -----------------------------------------------------------------------------
// Test B -- concurrent compress() (full pipeline, process-free).
//
// Same pattern as Test A, but through HtmlCompressor::compress() on independent
// HTML snippet copies, with a fixed level (AGGRESSIVE). Inline <script>/<style>
// content is minified by the native minifiers internally -- no subprocess is
// spawned. Each thread compares its output to the single-threaded reference for
// that snippet; assert zero mismatches.
//
// currentLevel is set ONCE here, before launching threads.
// -----------------------------------------------------------------------------
TEST(concurrent_compress_matches_single_threaded_reference) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   // Small, in-memory HTML snippets. Inline <script>/<style> route through the
   // native minifiers (no esbuild). No snippet triggers a bundler path.
   const std::vector<HtmlWork> work = {
      {"<div>  <span>hi</span>  </div><!-- gone -->"},
      {"<pre>a   b\n  c</pre>"},
      {"<style>body { color : red ; }</style>"},
      {"<script>// c\nvar a=1;</script>"},
      {"<ul>  <li>one</li>  <li>two</li>  </ul>"},
      {"<section><style>.x{margin:0}</style><script>var q = 1\nvar r = 2\n</script></section>"},
   };

   std::vector<std::string> reference;
   reference.reserve(work.size());
   for (const auto& w : work) {
      reference.push_back(HtmlCompressor::compress(w.input));
   }

   const int kThreads = 32;
   const int kIterations = 200;

   std::atomic<long long> mismatches{0};
   std::atomic<long long> comparisons{0};

   std::vector<std::thread> threads;
   threads.reserve(kThreads);

   for (int t = 0; t < kThreads; ++t) {
      threads.emplace_back([&work, &reference, &mismatches, &comparisons]() {
         bool localMismatch = false;
         for (int iter = 0; iter < kIterations; ++iter) {
            for (std::size_t i = 0; i < work.size(); ++i) {
               // compress() takes the input by const&, but each call still
               // produces its own fresh output string; the input is not shared
               // mutable state.
               const std::string got = HtmlCompressor::compress(work[i].input);
               comparisons.fetch_add(1, std::memory_order_relaxed);
               if (got != reference[i]) {
                  localMismatch = true;
               }
            }
         }
         if (localMismatch) {
            mismatches.fetch_add(1, std::memory_order_relaxed);
         }
      });
   }

   for (auto& th : threads) {
      th.join();
   }

   CHECK_EQ(mismatches.load(), 0LL);
   CHECK_EQ(comparisons.load(),
            static_cast<long long>(kThreads) * kIterations *
               static_cast<long long>(work.size()));
}

// -----------------------------------------------------------------------------
// Sanity: the references themselves are valid, non-empty minified output, so a
// "zero mismatches" pass cannot be trivially satisfied by empty/degenerate
// output. (Mirrors the task-3 baseline expectations.)
// -----------------------------------------------------------------------------
TEST(concurrency_reference_outputs_are_valid) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   // Native minifier: global has no IIFE; scoped is IIFE-wrapped.
   const std::string global = minifyNative("// leading\nvar a=1;", "global");
   CHECK(!global.empty());
   CHECK(global.find("var a=1;") != std::string::npos);
   CHECK(global.rfind("(()=>{", 0) != 0); // not IIFE-wrapped

   const std::string scoped = minifyNative("const x=1;\n", "scoped");
   CHECK(scoped.rfind("(()=>{", 0) == 0); // IIFE opener
   const std::string suffix = "})();";
   CHECK(scoped.size() >= suffix.size());
   CHECK(scoped.compare(scoped.size() - suffix.size(), suffix.size(), suffix) == 0);

   // compress(): inline script minified, comment stripped.
   const std::string html =
      HtmlCompressor::compress("<script>// c\nvar a=1;</script>");
   CHECK(!html.empty());
   CHECK(html.find("var a=1;") != std::string::npos);
   CHECK(html.find("// c") == std::string::npos);
}
