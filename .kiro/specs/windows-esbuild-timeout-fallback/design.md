# Windows esbuild Timeout Fallback Bugfix Design

## Overview

The C++ compressor (`src/compression/functions/minifyJS.cpp`) shells out to the `esbuild`
bundler for the AGGRESSIVE and EXTREME compression levels. Under concurrent load
(many CSS/JS assets requested from one page), process-spawn congestion causes two
compounding failures:

1. `getBundlerPath()` re-probes `esbuild --version` on **every** call. Under a burst of
   concurrent spawns these probes fail/time out, so the code wrongly decides global esbuild is
   missing and downgrades to `npx --yes esbuild`, which spawns *more* processes and worsens the
   cascade.
2. Each esbuild invocation waits up to **20 seconds** (Windows: `runCommandHiddenWindows`
   timeout; Linux: `timeout 20s` prefix) before giving up, and on timeout the code path can still
   attempt the npx fallback, multiplying load.

The bug was originally **observed on Windows** (not reproduced under WSL). However, the fix is
applied **cross-platform (Windows AND Linux)** because each change is a strict improvement that
makes the Linux path faster and more robust without breaking its currently-working behavior. The
fix has three coordinated behavioral parts plus one cross-platform hardening:

- **Cache global esbuild detection** so a successful probe is not repeated on every request
  (thread-safe, since the library is invoked concurrently through FFI). The detection is cached
  once on **both platforms** via a thread-safe function-local static.
- **Never use `npx` on either platform.** If a cached/global esbuild is not available, or an
  esbuild run times out or fails, go **straight to the native internal minifier**
  `HtmlCompressor::minifyJS(js, scope)`.
- **Shorten the esbuild execution timeout to 7 seconds** per invocation on both platforms
  (Windows: `runCommandHiddenWindows(command, 7000)`; Linux: `timeout 7s` prefix).
- **Harden `makeTempFilename`** for uniqueness under concurrency (cross-platform, non-behavioral).

The native internal minifier remains the guaranteed fallback and always produces valid output.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug — on Windows or Linux, at
  AGGRESSIVE/EXTREME level, when concurrent process-spawn congestion makes global-detection probes
  and/or esbuild invocations slow or failing, causing an npx downgrade and/or a ~20s hang.
- **Property (P)**: The desired behavior for buggy inputs — a 7s per-esbuild timeout, a cached
  global-detection probe, no npx (on either platform), and immediate use of the native minifier on
  timeout/failure, always yielding valid minified output.
- **Preservation**: Behavior that must remain unchanged — BASIC-level internal-only minification,
  `PHPSPA_JS_BUNDLER` honoring, successful esbuild output for a given scope/level within budget,
  and the internal minifier's tokenization/IIFE-wrapping semantics, on both platforms.
- **getBundlerPath**: Function in `minifyJS.cpp` (anonymous namespace) that resolves which bundler
  command to run — env override FIRST (bypassing the cache), then cached global `esbuild`, else an
  **empty sentinel** meaning "no bundler available" on **both platforms** (no npx).
- **isGlobalEsbuildAvailable**: New cross-platform, cached detection helper. Its result is computed
  once via a thread-safe function-local static; the platform-specific probe internals differ
  (Windows: `runCommandHiddenWindows("esbuild --version", 2000)`; Linux:
  `std::system("esbuild --version > /dev/null 2>&1")`), but the cached RESULT is shared.
- **runBundler**: Function in `minifyJS.cpp` that writes the input to a temp file, builds the
  esbuild command line for the scope/level, invokes the bundler, and reads back the output.
- **runCommandHiddenWindows**: Windows-only helper that spawns `cmd.exe /C <command>` hidden and
  waits up to `timeoutMillis`; returns the process exit code, `-1` on spawn failure, or `-2` on timeout.
- **HtmlCompressor::minifyJS (2-arg)**: `minifyJS(std::string& js, const std::string& scope)` — the
  native/internal minifier. Guaranteed fallback.
- **HtmlCompressor::minifyJS (3-arg)**: `minifyJS(std::string& js, const std::string& scope, char* debugOutput)`
  — the entry point that gates BASIC vs esbuild and orchestrates fallback.
- **makeTempFilename**: Helper generating temp file names from a high-resolution clock counter.
- **HtmlCompressor::currentLevel**: `static Level` member holding the active compression level
  (BASIC/AGGRESSIVE/EXTREME), read by the minifiers.

## Bug Details

### Bug Condition

The bug manifests on Windows or Linux, at AGGRESSIVE or EXTREME level, when many compression
requests run concurrently. `getBundlerPath()` is re-probing global esbuild on every call, and
`runBundler` allows each esbuild invocation up to 20 seconds (Windows: `timeoutMillis = 20000`;
Linux: `timeout 20s` prefix). Under process-spawn congestion the probe either fails (wrongly
downgrading to `npx --yes esbuild`) or the invocation hangs and, on timeout, does not go straight
to the native minifier — instead the npx path may still be attempted, multiplying process load.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type CompressionRequest
  OUTPUT: boolean

  RETURN input.platform IN { Windows, Linux }
         AND input.level IN { AGGRESSIVE, EXTREME }
         AND ( input.globalProbeFailsUnderLoad
               OR input.esbuildInvocationExceedsTimeout )
END FUNCTION
```

### Examples

- **Global probe storm**: 30 concurrent asset requests each call `getBundlerPath()`, each spawning
  `esbuild --version`. Several probes fail under congestion → code returns `"npx --yes esbuild"`.
  *Expected*: a previously successful global detection is reused; no npx downgrade. (Both platforms.)
- **20s hang then npx**: an esbuild invocation is congested and hits the 20s timeout (Windows `-2`;
  Linux `timeout 20s`). Current code treats this like any non-zero status and may still be on the
  npx path. *Expected*: on timeout, drop straight to the native minifier; no npx.
- **Cascade under load**: as concurrency grows, npx spawns multiply, so more invocations time out.
  *Expected*: bounded per-invocation cost (7s max) and graceful native-minifier degradation.
- **Edge — esbuild responsive (few requests)**: with low concurrency, esbuild responds in well
  under 7s. *Expected*: unchanged — esbuild output is returned.

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- BASIC level must continue to use the internal minifier only, on all platforms.
- The `PHPSPA_JS_BUNDLER` environment variable must still be honored as the bundler command on
  all platforms (and must bypass the new global-detection cache).
- When esbuild succeeds within the (new 7s) budget, the esbuild-produced output for the given
  scope (scoped/global) and level (AGGRESSIVE/EXTREME) must be returned as today, on both platforms.
- The internal minifier's tokenization, comment stripping, semicolon insertion, spacing, and
  `scoped` IIFE-wrapping (`(()=>{ ... ;})();`) must be unchanged.

**Scope:**
All inputs that are NOT (Windows/Linux AND AGGRESSIVE/EXTREME AND congested) must be completely
unaffected by this fix. This includes:
- BASIC-level inputs on any platform.
- Inputs where the `PHPSPA_JS_BUNDLER` env override is set.
- Inputs where a valid bundler responds within the budget.

**Note:** The expected *correct* behavior for buggy inputs is defined in the Correctness Properties
section (Property 1). This section focuses on what must NOT change.

## Hypothesized Root Cause

Based on the bug description and the code in `minifyJS.cpp`, the likely causes are (all applying to
both the `#ifdef _WIN32` and the `#else` Linux branches):

1. **Per-call global-detection probe (primary).** `getBundlerPath()` runs the probe on every call
   (Windows: `runCommandHiddenWindows("esbuild --version", 2000)`; Linux:
   `std::system("esbuild --version > /dev/null 2>&1")`). There is no memoization, so under
   concurrent bursts each request spawns its own probe. Congested probes fail → the function
   returns `"npx --yes esbuild"`, which then spawns further processes and compounds the storm.

2. **npx as a fallback multiplies process spawns.** `npx --yes esbuild` resolves/launches through
   the Node toolchain, adding process spawns per invocation. Under load this is the main accelerant
   of the cascade on both platforms; it is never a good outcome here because the native minifier is
   a cheaper, in-process guaranteed fallback.

3. **Timeout not routed to native minifier, with a 20s budget.** `runBundler` collapses all
   non-zero results (including the Windows `-2` timeout signal and the Linux `timeout 20s` failure)
   into a generic "bundler failed" path. The 3-arg `minifyJS` does fall back to the internal
   minifier when `runBundler` returns `false`, but because `getBundlerPath()` may already have
   chosen npx, a *timeout* can occur on the npx command, i.e. the expensive path was taken before
   falling back. The 20s budget (Windows `20000`, Linux `timeout 20s`) also makes each such failure
   very slow.

4. **Temp filename collision risk under concurrency (secondary).** `makeTempFilename` uses only a
   high-resolution clock counter. Two threads sampling the clock within the same tick (or on
   platforms with coarse `high_resolution_clock` resolution) can produce identical names, causing
   cross-request temp-file interference.

## Correctness Properties

Property 1: Bug Condition - Cross-platform graceful degradation with 7s timeout and no npx

_For any_ input where the bug condition holds (isBugCondition returns true) — i.e. Windows or
Linux, AGGRESSIVE/EXTREME, under congestion — the fixed code SHALL use a cached (not per-call)
global-detection probe, SHALL apply a 7-second per-esbuild execution timeout, SHALL NOT launch
`npx` on either platform (including after a timeout), SHALL go straight to the native internal
minifier `HtmlCompressor::minifyJS(js, scope)` on esbuild timeout or unavailability, and SHALL
always produce valid minified output.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

Property 2: Preservation - Non-buggy inputs unchanged

_For any_ input where the bug condition does NOT hold (isBugCondition returns false) — BASIC-level
inputs, inputs with the `PHPSPA_JS_BUNDLER` env override set, and inputs where esbuild responds
within budget — the fixed code SHALL produce the same result as the original code, preserving BASIC
internal-only minification, `PHPSPA_JS_BUNDLER` honoring (bypassing the cache), successful esbuild
output for the given scope/level, and the internal minifier's tokenization and IIFE-wrapping
semantics, on both platforms.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

## Fix Implementation

All changes are in `src/compression/functions/minifyJS.cpp` unless noted. Each behavioral change is
applied to **both** the `#ifdef _WIN32` block and the `#else` (Linux) branch.

### Change 1 — Thread-safe cached global esbuild detection (cross-platform)

**Function**: `getBundlerPath` (and a new detection helper)

**Rationale**: Replace the per-call probe with a one-time, thread-safe detection whose result is
retained for the process lifetime, eliminating the probe storm on both platforms (Requirement 2.1,
root causes 1 & 2).

**Specific changes**:
1. Add a single cross-platform detection helper `isGlobalEsbuildAvailable()` that probes
   `esbuild --version` **at most once** and caches whether a global esbuild is available. The
   platform-specific probe internals differ, but the cached RESULT is shared via a thread-safe
   function-local static:
   ```
   // anonymous namespace
   bool isGlobalEsbuildAvailable() {
      // Function-local static: the probe runs exactly once, thread-safe under the
      // C++11+ static-init guarantee (project targets CMAKE_CXX_STANDARD 20).
      static const bool available = [] {
         #ifdef _WIN32
            return runCommandHiddenWindows("esbuild --version", 2000) == 0;
         #else
            return std::system("esbuild --version > /dev/null 2>&1") == 0;
         #endif
      }();
      return available;
   }
   ```
   A Meyers `static const bool` is thread-safe to initialize under C++20; it needs no explicit
   mutex. If explicit control is preferred, wrap the probe in `std::call_once` with a
   `std::once_flag` plus a `std::atomic<bool>` cache. Either way the cached value is read-only after
   init, so concurrent reads are safe on both platforms.

2. In `getBundlerPath`, keep the `PHPSPA_JS_BUNDLER` env override FIRST and UNCHANGED on both
   branches (Requirement 3.2) — the env override must bypass detection/cache entirely. Then, on
   both platforms:
   - If `isGlobalEsbuildAvailable()` is true → return `"esbuild"`.
   - **Else return an empty string (a sentinel meaning "no bundler available")** instead of
     `"npx --yes esbuild"`. Neither platform uses npx any more (root cause 2).

**Thread-safety note**: The only shared mutable state introduced is the cached detection result,
initialized exactly once via standard thread-safe static initialization and never mutated
afterward. No data race is possible.

### Change 2 — esbuild execution timeout 20s → 7s (cross-platform)

**Function**: `runBundler`

**Rationale**: Bound each esbuild invocation to 7s on both platforms (Requirement 2.3). This is a
per-esbuild-process timeout, not a whole-request timeout.

**Specific changes**:
1. In the `#ifdef _WIN32` branch of `runBundler`, change:
   ```
   int status = runCommandHiddenWindows(command, 20000); // 20 second timeout
   ```
   to
   ```
   int status = runCommandHiddenWindows(command, 7000); // 7 second esbuild execution timeout
   ```
2. In the `#else` (Linux) branch of `runBundler`, change the command prefix:
   ```
   command = "timeout 20s " + command + " 2>\"" + errorPath.string() + "\"";
   ```
   to
   ```
   command = "timeout 7s " + command + " 2>\"" + errorPath.string() + "\"";
   ```
   The Linux path relies on the coreutils `timeout` command already in use.
3. Leave the Windows default parameter `runCommandHiddenWindows(..., DWORD timeoutMillis = 20000)`
   and the detection-probe call (`..., 2000`) as-is; only the bundler-run call site changes to 7000.

### Change 3 — Route timeout/no-bundler straight to native minifier (cross-platform)

**Functions**: `getBundlerPath`, `runBundler`, and the 3-arg `HtmlCompressor::minifyJS`

**Rationale**: On both platforms, a timeout or an unavailable bundler must drop immediately to the
native minifier with no npx attempt (Requirements 2.2, 2.4, 2.5).

**Specific changes**:
1. In `runBundler`, after resolving the bundler on both branches, if `getBundlerPath` returned the
   empty sentinel (no global esbuild, no env override), short-circuit: do NOT build/spawn any
   command, append a debug note ("No bundler available; using internal minifier"), clean up the
   temp input file, and `return false` so the caller falls back to the native minifier. No npx.
2. Treat the timeout signal explicitly. On Windows `runCommandHiddenWindows` returns `-2` on
   timeout; on Linux the `timeout 7s` prefix yields a non-zero status (typically 124) when the
   budget is exceeded. In `runBundler`, when a timeout occurs, record a distinct debug reason
   ("esbuild timed out after 7s; using internal minifier"), clean up temp files, and `return false`.
   Because neither platform selects npx, there is no npx path to attempt after a timeout.
3. The 3-arg `HtmlCompressor::minifyJS` already falls back to the 2-arg native minifier when
   `runBundler` returns `false`. This behavior is retained and is exactly what handles the
   timeout/no-bundler cases — so on both platforms, `runBundler == false` ⇒ native minifier,
   guaranteed valid output (Requirement 2.5). No change to the BASIC gate at the top of the 3-arg
   overload (Requirement 3.1).

**Decision flow (Windows/Linux, AGGRESSIVE/EXTREME):**
```
minifyJS(js, scope, debug)                     [3-arg]
  └─ currentLevel == BASIC? ─ yes → minifyJS(js, scope)      [internal only]  (unchanged)
  └─ no → runBundler(js, scope, currentLevel, out, debug)
            ├─ getBundlerPath:
            │     env PHPSPA_JS_BUNDLER set? → use it  (unchanged, bypasses cache)
            │     else global esbuild cached-available? → "esbuild"
            │     else → "" (sentinel: no bundler)
            ├─ bundler == "" ? → return false  ──────────────┐
            ├─ run esbuild (7s timeout, both platforms)       │
            │     timeout? → return false ────────────────────┤
            │     status != 0 / no output? → return false ────┤
            │     success → read output, return true          │
            └─ return true → js = out (esbuild output)        │
  └─ runBundler false ────────────────────────────────────────┘→ minifyJS(js, scope)  [native fallback]
```
No branch reaches `npx` on either platform.

### Change 4 — Harden `makeTempFilename` uniqueness (cross-platform, non-behavioral)

**Function**: `makeTempFilename`

**Rationale**: Prevent temp-name collisions under concurrency (root cause 4). This is a safe,
shared-code improvement that does not change compression behavior. Already cross-platform.

**Specific changes**:
1. Compose the name from three sources: the high-resolution clock counter (as today), the current
   thread id, and a monotonic atomic counter:
   ```
   std::string makeTempFilename(const std::string& prefix, const std::string& extension) {
      static std::atomic<uint64_t> counter{0};
      const auto now = std::chrono::high_resolution_clock::now().time_since_epoch().count();
      const auto tid = std::hash<std::thread::id>{}(std::this_thread::get_id());
      const auto seq = counter.fetch_add(1, std::memory_order_relaxed);
      return prefix + std::to_string(now) + "_" + std::to_string(tid) + "_" + std::to_string(seq) + extension;
   }
   ```
2. Add `#include <atomic>` and `#include <thread>`. The `std::atomic` counter is the only added
   shared state and is inherently thread-safe.

## Testing Strategy

### Validation Approach

Two phases. First, surface counterexamples that demonstrate the bug on the *unfixed* code (behavior
observation), then verify the fix behaves correctly and preserves existing behavior. Because the
core defect is a process-orchestration issue that is awkward to reproduce deterministically in a
unit test, the C++ tests focus on what *is* deterministically testable in-process — the native
minifier correctness, IIFE wrapping, level gating, temp-name uniqueness, and thread-safety of
concurrent compression — while the timeout/npx/cache routing is validated by structural review plus
targeted unit tests around the decision seam (see below).

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE the fix, and confirm/refute the
root-cause hypotheses. If refuted, re-hypothesize.

**Test Plan**: Observe the unfixed behavior via (a) code inspection of the two decision points on
both branches and (b) a concurrency stress test that calls the compressor from many threads. On the
unfixed code, observe that `getBundlerPath` re-probes every call and that a timeout is not routed
distinctly from other failures.

**Test Cases**:
1. **Per-call probe** — assert (by instrumentation/inspection) that global detection is invoked on
   every `runBundler` call on unfixed code, on both platforms (will show repeated probing).
2. **npx downgrade under probe failure** — simulate a failing probe and observe the returned bundler
   string becomes `npx --yes esbuild` on unfixed code, on both platforms (will show downgrade).
3. **Timeout routing** — with a stubbed timeout (Windows `-2`; Linux `timeout` non-zero), observe
   the unfixed code does not have a distinct native-minifier route independent of npx (will show
   conflation and the 20s budget).
4. **Temp-name collision (edge)** — spawn many threads calling `makeTempFilename` rapidly and check
   for duplicates on unfixed code (may collide under coarse clock resolution).

**Expected Counterexamples**:
- Detection probe runs on every call; a failing probe yields the npx string.
- A timeout is treated like a generic failure with a 20s budget (Windows `20000`, Linux `20s`).
- Possible causes: no memoization, npx fallback, conflated timeout handling, clock-only temp names.

### Fix Checking

**Goal**: For all inputs where the bug condition holds, the fixed code produces the expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := minifyJS_fixed(input)
  ASSERT global_detection_probe_is_cached(input)   // not re-run per call, both platforms
  ASSERT esbuild_execution_timeout(input) = 7000ms // Windows 7000; Linux timeout 7s
  ASSERT NOT used_npx(input)                        // neither platform uses npx
  ASSERT on_timeout_or_no_bundler(input) => used_native_minifier(result)
  ASSERT is_valid_minified_output(result)
END FOR
```

### Preservation Checking

**Goal**: For all inputs where the bug condition does NOT hold, the fixed code produces the same
result as the original.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT minifyJS_original(input) = minifyJS_fixed(input)
  // Covers: BASIC internal-only, PHPSPA_JS_BUNDLER env override, responsive esbuild
  //         within budget, and internal minifier semantics — on both platforms.
END FOR
```

**Testing Approach**: Property-based / high-volume testing is recommended for preservation because
it generates many inputs across the domain (varied JS, scope=global|scoped, level) and catches edge
cases. Preservation is strongest for the native minifier output, which the C++ tests can assert
directly and deterministically.

**Test Cases**:
1. **Native minifier output preservation** — for a corpus of JS snippets, assert the fixed native
   minifier output equals the pre-fix output (golden strings), for scope=global and scope=scoped.
2. **IIFE wrapping preservation** — scoped output is wrapped exactly as `(()=>{ ... ;})();` with the
   trailing `;`/whitespace trimming behavior unchanged.
3. **Level gating preservation** — BASIC uses internal minifier only; EXTREME strips block comments
   while AGGRESSIVE does not; `minifyCSS` no-ops below AGGRESSIVE.
4. **`PHPSPA_JS_BUNDLER` honored** — when the env var is set, `getBundlerPath` returns it verbatim,
   bypassing the detection cache (assert via the decision seam, on both platforms).

### C++ Test Harness (under `testing/`)

**Location & build**: Create a `testing/` directory at the repo root for all C++ tests. Add a
`testing/CMakeLists.txt` and wire it into the root `CMakeLists.txt` behind an option so the shared
`compressor` library build is unaffected by default:
```
# root CMakeLists.txt (append)
option(BUILD_TESTING_CPP "Build the C++ compressor tests" OFF)
if(BUILD_TESTING_CPP)
    enable_testing()
    add_subdirectory(testing)
endif()
```
`testing/CMakeLists.txt` compiles the compression sources (or links a small static lib built from
`src/compression/**`) together with the test `main`, and registers tests via `add_test` so
`ctest` can run them. Configure with `-DBUILD_TESTING_CPP=ON`.

**Framework**: A lightweight, single-header assertion harness (no external dependencies) —
`testing/test_framework.hpp` providing `CHECK(cond)`, `CHECK_EQ(a,b)`, a simple test registry, and
a `main` that runs all registered tests and returns non-zero on failure. This keeps the build free
of heavy deps (consistent with the size-optimized CMake setup) while remaining `ctest`-friendly.

**Accessing private members**: `minifyHTML`/`minifyCSS`/`optimizeAttributes` are `private`; the
public seams are `HtmlCompressor::compress`, the two `minifyJS` overloads, and `currentLevel`.
Tests should drive behavior through these public entry points. Where a test needs a private routine
directly (e.g. `minifyCSS`), add a minimal test seam — a `friend`-based accessor header or a
compile-time `PHPSPA_TESTING` guard exposing thin wrappers — rather than changing member visibility
in production. Prefer testing through `compress()`/`minifyJS` first; only add a seam if necessary.

**Planned test files under `testing/`**:
- `test_framework.hpp` — single-header harness.
- `test_minify_js.cpp` — native `minifyJS` correctness: comment stripping, semicolon insertion,
  spacing, string/regex handling; scope=global vs scope=scoped IIFE wrapping; EXTREME vs AGGRESSIVE
  block-comment behavior via `currentLevel`.
- `test_minify_css.cpp` — `minifyCSS` correctness and the below-AGGRESSIVE no-op gate.
- `test_minify_html.cpp` / `test_compress.cpp` — `compress()` pipeline and level gating
  (BASIC minifies HTML only; AGGRESSIVE also removes comments; EXTREME behavior).
- `test_temp_filename.cpp` — spawn many threads calling the temp-name generator (exposed via a test
  seam) and assert all generated names are unique.
- `test_bundler_decision.cpp` — decision-seam tests exercised on both platforms where testable:
  assert that with no env override and a stubbed unavailable esbuild, `getBundlerPath` returns the
  empty sentinel (no npx); that the env override bypasses the cache; that the detection probe is
  cached (invoked at most once across repeated calls); and that a stubbed timeout routes to the
  native minifier. Use a stub/injection around the probe and the run command under `PHPSPA_TESTING`.
- `test_concurrency.cpp` — thread-safety: spawn many threads calling `compress()` / native
  `minifyJS` concurrently on independent input strings; assert no crash and each output is valid
  minified output. Explicitly document and exercise the **shared static `currentLevel`** concern:
  set `currentLevel` once before launching threads (no per-thread mutation), since `currentLevel`
  is process-global mutable state and concurrent writes would be a data race. The test asserts that
  concurrent *reads* with a fixed level are safe and produce correct per-thread output. A note in
  the test documents that mixing levels across concurrent calls is unsupported by the current design
  (a finding for potential follow-up, out of scope for this fix).

### Unit Tests

- Native `minifyJS` correctness for scope=global and scope=scoped (IIFE wrapping).
- Level gating: BASIC (internal only), AGGRESSIVE, EXTREME (block-comment stripping).
- `minifyCSS` no-op below AGGRESSIVE; correct minification at/above AGGRESSIVE.
- Decision-seam (both platforms where testable): `PHPSPA_JS_BUNDLER` honored and bypasses the cache;
  returns empty sentinel (no npx) when no global esbuild; detection is cached (probe runs at most
  once); a timeout routes to the native fallback (via a stub/injection around the run command and
  probe under `PHPSPA_TESTING`).

### Property-Based Tests

- Generate varied JS inputs and assert the native minifier is idempotent-enough (re-minifying
  minified output does not corrupt it) and preserves string/template-literal contents.
- Generate random scope/level combinations and assert output validity (balanced IIFE wrapping for
  scoped, no wrapping for global).
- Generate many concurrent compression calls and assert no crashes and valid output across all.

### Integration Tests

- Full `compress()` flow across BASIC/AGGRESSIVE/EXTREME on representative HTML with inline JS/CSS.
- Concurrency stress: many threads through `compress()` with a fixed `currentLevel`, asserting valid
  output and no crashes (simulates the real concurrent-asset scenario at the in-process level).
- Confirm (by review + seam test) that on neither platform does any code path spawn `npx`, that the
  detection probe is cached, and that esbuild success within 7s still returns esbuild output.
