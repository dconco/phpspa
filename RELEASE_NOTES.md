# v2.1.6 — Critical Security Hardening, C++ Meta Tag Quote Preservation, FrankenPHP Worker Mode & Oxc Minifier

> [!CAUTION]
> **CRITICAL SECURITY RELEASE**: This release patches multiple high-severity vulnerabilities including Remote Code Execution (RCE), PHP Object Injection, and Server-Side Request Forgery (SSRF). All users and deployments must upgrade to **`v2.1.6`** immediately.

```bash
composer require dconco/phpspa:^2.1.6
```

---

## 🛡️ Security Fixes & Deserialization Defusal

- **Arbitrary PHP Execution in `Response::sendFile()`**: Refused execution of `.php` files inside `Response::sendFile()`. Previously, `sendFile()` executed `.php` files via `require`, which allowed arbitrary code execution (RCE) if user-influenced paths were served. It now throws `\InvalidArgumentException`.
- **Eliminated Deserialization in `phpspa.__call`**: Replaced untrusted serialized PHP tokens in client-server RPC function calls with strict, versioned JSON tokens (`FunctionCallToken`). Legacy serialized tokens are permanently rejected.
- **Defused SSRF POP Gadget in `PendingRequest`**: Removed network I/O (`fetchDataIfNeeded()`) from `PendingRequest::__toString()`. String coercion of pending requests will never trigger network requests, preventing weaponization in deserialization gadget chains.
- **Session Object Injection Mitigation**: Configured `SessionHandler::get()` with `unserialize($sessionData, ['allowed_classes' => false])` to prevent untrusted or tampered session stores from instantiating arbitrary PHP objects.
- **POP Callback Gadget Neutralization in `ImportedFile`**: Hardened `ImportedFile::getRawContent()` to reject any argument-based calls, preventing its abuse as a `CURLOPT_READFUNCTION` file-read gadget.
- **HMAC Authentication for Component Values**: Component attribute values requiring PHP object preservation are now authenticated with a per-session HMAC before deserialization via `ComponentValueCodec`. Unsigned or untrusted values are treated strictly as plain text.
- **Strict Endpoint Hardening**: State updates and server-function calls now strictly enforce HTTP `POST`, rigorous Base64/JSON decoding, bounded payload sizes, and generic HTTP 400 error responses without leaking internal stack traces.
- **Component Tag Callable Restrictions**: Component tags can no longer execute internal PHP functions or internal class methods, and callables declared to return non-renderable objects are rejected before invocation.

### 📋 Affected Versions Breakdown

| Vulnerability / Flaw | Introduced In | Status in v2.1.6 |
|---|---|---|
| Object Injection via `phpspa.__call` RPC | Commit `64c84df` (Aug 2025) | ✅ **Patched** (JSON tokens only) |
| Session Deserialization (`SessionHandler::get`) | Commit `36fef02` (Aug 2025) | ✅ **Patched** (`allowed_classes => false`) |
| Direct PHP Execution in `Response::sendFile()` | Commit `b4e881b` (Dec 2025) | ✅ **Patched** (Refuses `.php`) |
| SSRF POP Gadget in `PendingRequest::__toString` | Commit `1e49f29` (Oct 2025) | ✅ **Defused** (No auto-fetch) |
| Callback Gadget in `ImportedFile::getRawContent` | Core Import system (Oct 2025) | ✅ **Neutralized** |
| Component Tag Unauthenticated Object Attributes | Dynamic parser (Sep 2025) | ✅ **Hardened** (HMAC signed) |

---

## ⚡ Performance & HTML Compression

- **Meta Tag Attribute Quote Preservation for Social Crawlers**:
  The C++ HTML compressor (`minifyHTML.cpp` / `optimizeAttributes.cpp`) and the PHP fallback compressor now preserve attribute quotes on all `<meta>` tags across all compression levels (including `EXTREME`). 
  Social scrapers (WhatsApp, Facebook, LinkedIn, X, and iMessage link preview parsers) fail to extract titles, descriptions, and preview images when quotes are dropped (e.g. `<meta property=og:image>`). Attributes on `<meta>` tags now retain their quotes, and unquoted attributes on `<meta>` tags are automatically quoted, while all other HTML tags (`div`, `span`, `p`, etc.) continue to have safe quotes removed as normal for maximum compression.

---

## ✨ New Features & Enhancements

### 1. FrankenPHP / Worker Mode Compatibility
- Eliminated `exit()` calls from the request lifecycle when using `App->run(true)`, enabling persistent worker mode (FrankenPHP, RoadRunner, etc.) where the script must return output instead of terminating.
- **CORS preflight (OPTIONS)**: Returns the `PREFLIGHT_HANDLED` constant instead of exiting. Check with `$output === \PREFLIGHT_HANDLED`.
- **Expired assets**: Returns `"Asset has expired"` string with 410 status code instead of exiting.
- **Redirects**: `$response->redirect()` now returns `$this` instead of exiting, and `Redirect()` throws `RedirectException` caught cleanly by the worker loop.

### 2. SPA Navigation Flicker Fix (Deferred Style Removal)
- Fixed visual flicker during SPA navigation caused by removing the outgoing page's stylesheets before the incoming DOM swap completed.
- Style removal is now deferred until **after** the new DOM is applied via `commitStyles()`.
- Added `Link` header preload hints for incoming stylesheets.

### 3. Advanced Route Parameter Constraints
- **Range Constraints**: `int<min,max>`, `int<min>`, and exact `int<val,val>`.
- **Enum Constraints**: `enum<draft,published,archived>` matches only whitelisted values.
- **Literal Prefixes & Suffixes**: Route segments can now specify prefixes/suffixes, e.g. `/profile/@{username}` or `/order/#{id: int}`.

### 4. Pluggable Native JavaScript Minification Engines
- Supports C++ (default bundled), esbuild, and the high-speed Rust-based **Oxc Minifier** (`useOxcForJsMinification()`).
- Oxc shared libraries for Linux, Windows, and macOS are automatically built and attached below as release assets.

---

## ⚠️ Breaking Changes
- **Explicit HTTP Requests Required for `useFetch`**: String casting a `PendingRequest` (e.g. `echo useFetch(...)`) no longer auto-triggers an HTTP GET request. Use `echo useFetch(...)->get()`.
- **`Response::sendFile()` Refuses `.php` Files**: Passing a path to a `.php` file throws `\InvalidArgumentException`.
- **Legacy Function Call Tokens Incompatible**: Client bundles and backend runtime must be updated together.
- **HTTP POST Required for State & RPC**: Function calls now require HTTP `POST`.
