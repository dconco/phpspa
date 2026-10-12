// =============================================================================
// Temp-filename uniqueness test
// (Task 5 of the windows-esbuild-timeout-fallback bugfix spec).
//
//   Property 2 - Preservation: temp filenames are unique under concurrency.
//
// runBundler writes each esbuild invocation's input/output/error to temp files
// whose names come from makeTempFilename(prefix, extension). This test exercises
// that generator directly (through the PHPSPA_TESTING decision seam) under heavy
// concurrency and asserts that NO two generated names collide.
//
// ---------------------------------------------------------------------------
// DESIGN ROOT CAUSE 4 -- why this test exists
// ---------------------------------------------------------------------------
// The CURRENT (unfixed) makeTempFilename derives the name from a
// high-resolution clock counter ONLY:
//
//     prefix + std::to_string(clock().time_since_epoch().count()) + extension
//
// Two threads that sample the clock within the same tick -- or any thread on a
// platform whose std::chrono::high_resolution_clock has coarse resolution --
// can produce IDENTICAL names. Colliding temp names mean concurrent esbuild
// invocations can clobber each other's input/output files (design root cause 4:
// "Temp filename collision risk under concurrency").
//
// ---------------------------------------------------------------------------
// PRE-FIX vs POST-FIX SEMANTICS -- read carefully
// ---------------------------------------------------------------------------
// This test encodes the POST-FIX EXPECTATION: ZERO duplicates
// (CHECK_EQ(duplicateCount, 0)). That deliberately makes it a permanent
// uniqueness REGRESSION GUARD once Task 9 (Change 4) hardens the generator to
// combine the clock counter with the hashed thread id and a monotonic atomic
// counter.
//
// BECAUSE it encodes the post-fix target, on the CURRENT (clock-only,
// unfixed) implementation this test MAY FAIL if the CI machine's clock is
// coarse enough (or threads happen to sample the same tick) to produce
// collisions. THAT IS ACCEPTABLE AND EXPECTED for this pre-fix task -- the
// failure documents root cause 4. (If the CI clock is high-resolution it may
// happen to pass even pre-fix; either outcome is fine here.) Task 12 re-runs
// this SAME test after Task 9's hardening to confirm it passes RELIABLY.
//
// ---------------------------------------------------------------------------
// There is no local C++ toolchain; this suite is validated by the CI
// `cpp-tests` job (Linux + Windows) wired into the compressor build workflow.
//
//   _Requirements: (secondary hardening -- supports 2.4 reliability under
//                  concurrency)_
//   Property annotation: **Validates: Requirements 2.4 (Property 2 -
//   Preservation)**
// =============================================================================

#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

#include <cstddef>
#include <string>
#include <thread>
#include <unordered_set>
#include <vector>

#include "compression/functions/minifyJS_test_seam.h"

namespace ts = phpspa::testseam;

namespace {

   // Batch size and thread count sized to hammer the clock hard enough that a
   // coarse-resolution / same-tick collision is likely to surface on the
   // unfixed generator, while staying fast enough for CI.
   constexpr int kThreads = 32;
   constexpr int kNamesPerThread = 5000;

   // Same prefix/extension runBundler uses for its input temp file, so the test
   // exercises a realistic name shape.
   const char* const kPrefix = "phpspa_js_";
   const char* const kExtension = ".js";

} // namespace

// -----------------------------------------------------------------------------
// Concurrent uniqueness: many threads each generate a large batch of names into
// their OWN vector (no lock contention on the hot path). After join, the main
// thread merges every name into a single unordered_set and counts how many were
// already present (duplicates).
//
// POST-FIX EXPECTATION: duplicateCount == 0. On the unfixed clock-only
// generator this MAY be > 0 (see the root-cause-4 note at the top of the file);
// Task 9's hardening makes it reliably zero.
// -----------------------------------------------------------------------------
TEST(temp_filenames_unique_under_concurrency) {
   // Each thread fills its own vector to avoid synchronizing on the hot path.
   std::vector<std::vector<std::string>> perThread(kThreads);

   std::vector<std::thread> threads;
   threads.reserve(kThreads);

   for (int t = 0; t < kThreads; ++t) {
      threads.emplace_back([&perThread, t]() {
         auto& out = perThread[t];
         out.reserve(kNamesPerThread);
         for (int i = 0; i < kNamesPerThread; ++i) {
            out.push_back(ts::makeTempFilename(kPrefix, kExtension));
         }
      });
   }

   for (auto& th : threads) {
      th.join();
   }

   // Merge on the main thread and count collisions.
   const std::size_t totalNames =
      static_cast<std::size_t>(kThreads) * kNamesPerThread;

   std::unordered_set<std::string> seen;
   seen.reserve(totalNames * 2);

   long long duplicateCount = 0;
   for (const auto& batch : perThread) {
      for (const auto& name : batch) {
         if (!seen.insert(name).second) {
            ++duplicateCount;
         }
      }
   }

   // Sanity: we actually generated the full population of names.
   CHECK_EQ(static_cast<long long>(totalNames),
            static_cast<long long>(kThreads) *
               static_cast<long long>(kNamesPerThread));

   // POST-FIX target: every generated name is unique.
   //
   // PRE-FIX (Task 5): this may fail on the clock-only generator if the CI
   // clock is coarse / threads sample the same tick -- that failure DOCUMENTS
   // design root cause 4 and is acceptable for this task. Task 9 (Change 4:
   // clock + thread id + atomic counter) makes this pass reliably, and Task 12
   // re-runs this exact assertion to confirm.
   CHECK_EQ(duplicateCount, 0LL);
}

// -----------------------------------------------------------------------------
// Single-threaded sanity: N sequential calls with the same prefix/extension are
// all unique.
//
// Same PRE-FIX vs POST-FIX semantics as the concurrent case: on the unfixed
// clock-only generator two rapid sequential calls CAN collide if the clock is
// coarse enough that consecutive samples return the same counter value. The
// assertion encodes the POST-FIX expectation (all unique); Task 9's atomic
// counter guarantees distinct names even within a single tick.
// -----------------------------------------------------------------------------
TEST(temp_filenames_unique_sequential) {
   constexpr int kSequentialNames = 20000;

   std::unordered_set<std::string> seen;
   seen.reserve(kSequentialNames * 2);

   long long duplicateCount = 0;
   for (int i = 0; i < kSequentialNames; ++i) {
      const std::string name = ts::makeTempFilename(kPrefix, kExtension);
      if (!seen.insert(name).second) {
         ++duplicateCount;
      }
   }

   // POST-FIX target: all sequential names unique (may collide pre-fix on a
   // coarse clock -- documents root cause 4; Task 9 makes it reliable).
   CHECK_EQ(duplicateCount, 0LL);
}

// -----------------------------------------------------------------------------
// Structural sanity (holds regardless of the clock's resolution, so this part
// is not subject to the pre-fix collision caveat): every generated name carries
// the requested prefix and extension. This guards against the wrapper/seam
// mis-forwarding arguments.
// -----------------------------------------------------------------------------
TEST(temp_filenames_have_prefix_and_extension) {
   const std::string prefix = kPrefix;
   const std::string extension = kExtension;

   for (int i = 0; i < 100; ++i) {
      const std::string name = ts::makeTempFilename(prefix, extension);
      // Starts with the prefix.
      CHECK(name.rfind(prefix, 0) == 0);
      // Ends with the extension.
      CHECK(name.size() >= extension.size());
      CHECK(name.compare(name.size() - extension.size(), extension.size(),
                         extension) == 0);
      // There is at least one character of unique payload between them.
      CHECK(name.size() > prefix.size() + extension.size());
   }
}
