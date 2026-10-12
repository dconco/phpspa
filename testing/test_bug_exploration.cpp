// =============================================================================
// Bug condition exploration test  (Task 2 of the windows-esbuild-timeout-fallback
// bugfix spec).  Property 1 - cross-platform graceful degradation.
//
// CRITICAL SEMANTICS -- READ BEFORE "FIXING" ANYTHING HERE:
//   This test encodes the EXPECTED (POST-FIX) behavior of the bundler decision
//   seam.  On the CURRENT, UNFIXED code it is EXPECTED TO FAIL, and that
//   failure is exactly what CONFIRMS the bug exists.  DO NOT modify the
//   production code to make it pass -- the fix is applied in tasks 6-9 (design
//   Changes 1-3), after which this same test file passes UNCHANGED.
//
//   There is no local C++ toolchain, so the failure cannot be observed on this
//   machine; it is validated by the CI `cpp-tests` job (Linux + Windows) wired
//   into .github/workflows/build-compressor.yml.
//
// WHY THE DECISION SEAM (and not real esbuild):
//   The real defect is process-orchestration under concurrent spawn congestion,
//   which is not deterministically reproducible in a unit test.  So we assert
//   the in-process DECISION LOGIC via the phpspa::testseam bridge that
//   minifyJS.cpp exposes only under PHPSPA_TESTING.  The bridge mirrors the
//   exact current decision points and spawns no real process.
//
// -----------------------------------------------------------------------------
// EXPECTED COUNTEREXAMPLES ON UNFIXED CODE (these confirm the root causes):
//
//   * Timeout budget:   bundlerRunTimeoutMillis() == 20000  (Linux `timeout 20s`)
//                       -> CHECK for 7000 FAILS now.                (Req 2.3)
//
//   * Probe failure ->  resolveBundler(nullptr, /*probeSucceeds=*/false)
//     npx downgrade:      == "npx --yes esbuild"   (should be the "" sentinel)
//                       -> CHECK for empty sentinel FAILS now.  (Req 2.1, 2.2)
//
//   * Timeout not      timeoutBuildsNpxCommand(false, <timeout>) == true
//     routed cleanly:    (an npx command is constructed and attempted before the
//                        native fallback, because getBundlerPath already picked npx)
//                       -> CHECK for "no npx command" FAILS now.    (Req 2.4)
//
//   The native-fallback invariant (Req 2.5) already holds today and stays as a
//   passing guard.
// =============================================================================

#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

#include <string>

#include "compression/HtmlCompressor.h"
#include "compression/functions/minifyJS_test_seam.h"

namespace ts = phpspa::testseam;

// Windows signals a run timeout as -2 (runCommandHiddenWindows); Linux's
// `timeout` coreutil yields a non-zero status, conventionally 124.
static constexpr int kWindowsTimeoutStatus = -2;
static constexpr int kLinuxTimeoutStatus = 124;

// -----------------------------------------------------------------------------
// CHECK 1 -- Req 2.3: a 7-second per-esbuild EXECUTION timeout on both platforms.
// UNFIXED: 20000 (Windows) / `timeout 20s` (Linux) -> this FAILS now.
// -----------------------------------------------------------------------------
TEST(bug_exploration_timeout_budget_is_7_seconds) {
   // Post-fix expectation: 7000 ms (Windows 7000; Linux `timeout 7s`).
   CHECK_EQ(ts::bundlerRunTimeoutMillis(), 7000);
}

// -----------------------------------------------------------------------------
// CHECK 2 -- Req 2.1 / 2.2: when the global-esbuild probe fails under load and
// no PHPSPA_JS_BUNDLER override is set, the resolved bundler is the empty
// sentinel (no bundler) on BOTH platforms -- NOT "npx --yes esbuild".
// UNFIXED: resolves to "npx --yes esbuild" -> this FAILS now.
// -----------------------------------------------------------------------------
TEST(bug_exploration_probe_failure_yields_no_bundler_not_npx) {
   const std::string resolved = ts::resolveBundler(/*envValue=*/nullptr,
                                                   /*globalProbeSucceeds=*/false);

   // Post-fix: empty sentinel meaning "no bundler available".
   CHECK_EQ(resolved, std::string(ts::noBundlerSentinel()));
   // And it must never be an npx command on either platform.
   CHECK(!ts::commandUsesNpx(resolved));

   // Sanity guards that hold both before and after the fix (document that the
   // env override and a successful probe are unaffected by this change):
   //   - env override is honored verbatim and bypasses the probe.
   CHECK_EQ(ts::resolveBundler("my-bundler", /*globalProbeSucceeds=*/false),
            std::string("my-bundler"));
   //   - a successful global probe resolves to "esbuild".
   CHECK_EQ(ts::resolveBundler(nullptr, /*globalProbeSucceeds=*/true),
            std::string("esbuild"));
}

// -----------------------------------------------------------------------------
// CHECK 3 -- Req 2.4: when a bundler run times out, the decision routes STRAIGHT
// to the native minifier and NO npx command is constructed, on both platforms.
// UNFIXED: the timeout is conflated with generic failure and getBundlerPath has
// already picked npx, so an npx command IS built -> this FAILS now.
// -----------------------------------------------------------------------------
TEST(bug_exploration_timeout_routes_to_native_without_npx) {
   // Model detection failing under load (globalProbeSucceeds = false), then a
   // run timeout on each platform's timeout signal.
   for (int status : {kWindowsTimeoutStatus, kLinuxTimeoutStatus}) {
      // Falls back to the native minifier ...
      CHECK(ts::timeoutRoutesToNative(/*globalProbeSucceeds=*/false, status));
      // ... but must do so WITHOUT ever constructing an npx command.
      CHECK(!ts::timeoutBuildsNpxCommand(/*globalProbeSucceeds=*/false, status));
   }
}

// -----------------------------------------------------------------------------
// CHECK 4 -- Req 2.5 (invariant): the native fallback always yields valid
// minified output.  This part may PASS even on unfixed code; it is kept as the
// standing invariant that the timeout / no-bundler paths degrade into.
// -----------------------------------------------------------------------------
TEST(bug_exploration_native_fallback_produces_valid_output) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   // global scope: no IIFE wrapping, comments stripped, whitespace collapsed.
   {
      std::string js =
         "// a leading comment\n"
         "const   answer   =   42 ;\n"
         "function   greet ( name )  {\n"
         "   return  'hi '  +  name ;\n"
         "}\n";
      HtmlCompressor::minifyJS(js, "global");

      CHECK(!js.empty());
      // Line comment must be gone.
      CHECK(js.find("// a leading comment") == std::string::npos);
      // Core tokens survive minification.
      CHECK(js.find("answer") != std::string::npos);
      CHECK(js.find("function") != std::string::npos);
      // String literal content is preserved verbatim.
      CHECK(js.find("'hi '") != std::string::npos);
   }

   // scoped: wrapped in an IIFE `(()=>{ ... ;})();`.
   {
      std::string js = "const x = 1;\nconsole.log(x);\n";
      HtmlCompressor::minifyJS(js, "scoped");

      CHECK(!js.empty());
      CHECK(js.rfind("(()=>{", 0) == 0);          // starts with the IIFE opener
      CHECK(js.find("})();") != std::string::npos); // and closes the IIFE
   }
}
