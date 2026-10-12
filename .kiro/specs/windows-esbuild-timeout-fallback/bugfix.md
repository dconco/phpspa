# Bugfix Requirements Document

## Introduction

phpSPA's C++ compressor (`src/compression/functions/minifyJS.cpp`) uses the `esbuild`
bundler to minify JS/CSS assets at the AGGRESSIVE and EXTREME compression levels. When a
real project serves many CSS and JS files from a single HTML page, the browser requests
those assets concurrently, causing a burst of concurrent esbuild-related process spawns on
the server.

At the start of a page load (few concurrent requests) the GLOBAL `esbuild` is detected and
used successfully. But as the number of concurrent requests grows, compression begins to
FAIL: the tool reports that esbuild is "not globally found", falls back to `npx --yes esbuild`
(spawning even more processes), and that fallback also fails. The degradation is progressive
and load-dependent — it does not occur at the start, only once requests become many.

The root causes are process-spawn congestion combined with two design flaws:

1. **Per-call global-detection probe.** `getBundlerPath()` runs `esbuild --version` as a probe
   on EVERY compression call to decide global-vs-npx. Under a burst of concurrent process
   spawns, these probes fail or time out, so the code wrongly concludes esbuild is not
   installed globally and falls back to `npx --yes esbuild`, which spawns additional processes
   and worsens the cascade.
2. **Long execution timeout with npx fallback.** The esbuild execution timeout is 20 seconds
   (Windows: `runCommandHiddenWindows` `timeoutMillis = 20000`; Linux: `timeout 20s` prefix).
   Under congestion each call can hang near 20s before falling back to the internal minifier,
   and the npx path multiplies the process load rather than degrading gracefully.

A secondary concern to note: `runBundler` derives unique temp filenames from a high-resolution
clock (`makeTempFilename`); under heavy concurrency these could still collide.

The bug was originally OBSERVED on Windows. The user did NOT experience it under WSL, and WSL
did not exhibit the defect. However, the fixes are being applied **cross-platform (Windows AND
Linux)** because they are strict improvements: caching the detection probe, removing the npx
fallback, and shortening the execution timeout only make the Linux path faster and more robust
without breaking its currently-working behavior. The native internal minifier
(`HtmlCompressor::minifyJS(js, scope)`) is the guaranteed fallback and must always produce
valid output.

### Scope

- Cross-platform behavioral fix (Windows AND Linux), in the C++ compressor library under `src/`,
  specifically the esbuild bundler path in `minifyJS.cpp` and `getBundlerPath()`.
- The four fixes apply to both platforms: cached thread-safe detection probe, no npx fallback,
  7-second per-esbuild execution timeout, and hardened `makeTempFilename` uniqueness (already
  cross-platform).
- The Linux `timeout 20s` prefix becomes `timeout 7s`; the Windows timeout becomes
  `runCommandHiddenWindows(command, 7000)`.

### Bug Condition (Methodology)

**Bug Condition Function** — identifies inputs that trigger the bug:

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type CompressionRequest
  OUTPUT: boolean

  // Windows OR Linux, esbuild path (AGGRESSIVE or EXTREME level),
  // under concurrent load where global-detection probes and/or esbuild
  // invocations are slow or failing due to process-spawn congestion.
  RETURN X.platform IN { Windows, Linux }
     AND X.level IN { AGGRESSIVE, EXTREME }
     AND (X.globalProbeFailsUnderLoad OR X.esbuildInvocationExceedsTimeout)
END FUNCTION
```

**Property — Fix Checking** (desired behavior for buggy inputs):

```pascal
// Property: Fix Checking - graceful degradation under concurrency (Windows AND Linux)
FOR ALL X WHERE isBugCondition(X) DO
  result <- minifyJS'(X)
  ASSERT esbuild_execution_timeout(X) = 7_seconds
  ASSERT global_detection_probe_is_cached(X)          // probe not re-run per call
  ASSERT NOT launched_npx(X)                          // npx never launched
  ASSERT on_esbuild_timeout_or_unavailable(X) => used_native_minifier(result)
  ASSERT is_valid_minified_output(result)
END FOR
```

**Property — Preservation Checking** (non-buggy inputs unchanged):

```pascal
// Property: Preservation Checking (Windows AND Linux)
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT minifyJS(X) = minifyJS'(X)
  // Covers: BASIC level (internal only), PHPSPA_JS_BUNDLER env override,
  //         responsive esbuild within budget, internal minifier semantics.
END FOR
```

**Key Definitions:**
- **F** = `HtmlCompressor::minifyJS` / bundler path as it exists today (before the fix).
- **F'** = the fixed function after applying the cross-platform changes.

## Bug Analysis

### Current Behavior (Defect)

What currently happens on Windows and Linux when many JS/CSS assets are requested concurrently.

1.1 WHEN the number of concurrent compression requests grows large THEN the system re-runs `esbuild --version` as a per-call probe in `getBundlerPath()`, and under process-spawn congestion these probes fail or time out
1.2 WHEN a per-call `esbuild --version` probe fails under load THEN the system wrongly concludes that global esbuild is not installed and falls back to `npx --yes esbuild`, spawning additional processes that worsen the congestion cascade
1.3 WHEN an esbuild invocation is congested THEN the system waits up to 20 seconds (Windows: `runCommandHiddenWindows` `timeoutMillis = 20000`; Linux: `timeout 20s` prefix) before giving up, causing each request to hang for a long time
1.4 WHEN an esbuild invocation times out THEN the system does NOT go straight to the native minifier and instead may continue attempting the npx fallback path, multiplying process load
1.5 WHEN the global esbuild is available at the start of a page load THEN the system uses it successfully, but the successful detection is not retained, so later concurrent requests re-probe and degrade

### Expected Behavior (Correct)

What should happen instead on Windows and Linux.

2.1 WHEN the number of concurrent compression requests grows large THEN the system SHALL cache the esbuild global-detection result (thread-safe) so `esbuild --version` is not re-run on every compression call, avoiding degradation caused by transient probe failures under load
2.2 WHEN there is no global esbuild and no `PHPSPA_JS_BUNDLER` env override THEN the system SHALL go STRAIGHT to the native internal minifier and SHALL NOT fall back to `npx` on either platform
2.3 WHEN an esbuild invocation is run THEN the system SHALL apply a 7-second esbuild EXECUTION timeout per esbuild invocation on both platforms (Windows: `runCommandHiddenWindows(command, 7000)`; Linux: `timeout 7s` prefix), not a 7-second timeout for the whole HTTP request
2.4 WHEN an esbuild invocation times out or esbuild is unavailable THEN the system SHALL go STRAIGHT to the native/internal compressor (`HtmlCompressor::minifyJS(js, scope)`) and SHALL NOT launch `npx` as a fallback on either platform
2.5 WHEN the native/internal minifier is used as the fallback THEN the system SHALL always produce valid minified output
2.6 WHEN esbuild is available and responsive THEN the system SHALL still use it to produce the bundled/minified output within the 7-second execution budget on both platforms

### Unchanged Behavior (Regression Prevention)

Existing behavior that must be preserved on Windows and Linux.

3.1 WHEN the compression level is BASIC THEN the system SHALL CONTINUE TO use the internal minifier only, unchanged on all platforms
3.2 WHEN the `PHPSPA_JS_BUNDLER` environment variable is set THEN the system SHALL CONTINUE TO honor it as the bundler command on all platforms, bypassing the detection cache
3.3 WHEN esbuild succeeds within the execution timeout THEN the system SHALL CONTINUE TO return the esbuild-produced output for the given scope (scoped/global) and level (AGGRESSIVE/EXTREME) as it does today, on both platforms
3.4 WHEN a request is not under concurrency-induced congestion (few requests, responsive esbuild within budget) THEN the system SHALL CONTINUE TO behave as it currently does, including successful global esbuild usage
3.5 WHEN the internal minifier processes JS THEN the system SHALL CONTINUE TO apply the same tokenization, comment stripping, semicolon-insertion, spacing, and `scoped` IIFE-wrapping behavior it currently produces
