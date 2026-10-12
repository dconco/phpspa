#ifndef PHPSPA_MINIFYJS_TEST_SEAM_H
#define PHPSPA_MINIFYJS_TEST_SEAM_H

// -----------------------------------------------------------------------------
// Test-only decision seam for minifyJS.cpp.
//
// The bundler-decision helpers (getBundlerPath / runBundler / the timeout
// handling) live in an anonymous namespace inside minifyJS.cpp and are not
// reachable from a separate translation unit. When minifyJS.cpp is compiled
// with PHPSPA_TESTING defined, it also compiles an external-linkage bridge
// (namespace phpspa::testseam) that mirrors the CURRENT decision logic without
// spawning any real process. Tests include this header to observe:
//
//   - the per-esbuild-invocation execution timeout used at the run site,
//   - the bundler command getBundlerPath would resolve for an injected global
//     probe result (env override / global esbuild / fallback), and
//   - whether an injected timeout routes to the native minifier and whether an
//     npx command was constructed on the way there.
//
// This header (and the bridge it declares) exists ONLY under PHPSPA_TESTING.
// The default `compressor` shared-library build never defines PHPSPA_TESTING,
// so production behavior is completely unaffected.
// -----------------------------------------------------------------------------

#include <string>

namespace phpspa {
namespace testseam {

// Per-esbuild-invocation execution timeout in milliseconds, as applied at the
// runBundler run site. UNFIXED: 20000. POST-FIX (Change 2): 7000.
int bundlerRunTimeoutMillis();

// Mirrors getBundlerPath resolution with an INJECTED global probe result:
//   envValue non-empty       -> returns it verbatim (override, bypasses probe)
//   else globalProbeSucceeds -> "esbuild"
//   else                     -> UNFIXED: "npx --yes esbuild"; POST-FIX: ""
std::string resolveBundler(const char* envValue, bool globalProbeSucceeds);

// True if the resolved command references npx.
bool commandUsesNpx(const std::string& bundler);

// The empty-string sentinel meaning "no bundler available" (post-fix).
const char* noBundlerSentinel();

// For an injected run status (Windows -2 / Linux non-zero) and probe result,
// whether the decision falls back to the native minifier.
bool timeoutRoutesToNative(bool globalProbeSucceeds, int timeoutStatus);

// For the same injected inputs, whether an npx command was constructed before
// reaching the native fallback (the defect: npx is attempted first).
bool timeoutBuildsNpxCommand(bool globalProbeSucceeds, int timeoutStatus);

// Forwards to the anonymous-namespace makeTempFilename in minifyJS.cpp so tests
// can exercise the temp-name generator under concurrency (Task 5, Property 2 -
// Preservation: temp filenames unique). UNFIXED: derives the name from a
// high-resolution clock counter ONLY, so concurrent callers can collide (design
// root cause 4). POST-FIX (Change 4 / Task 9): clock + thread id + atomic
// counter, making names reliably unique. This declaration exists ONLY under
// PHPSPA_TESTING.
std::string makeTempFilename(const std::string& prefix, const std::string& extension);

} // namespace testseam
} // namespace phpspa

#endif // PHPSPA_MINIFYJS_TEST_SEAM_H
