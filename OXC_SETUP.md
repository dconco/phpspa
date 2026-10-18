### 1. Prerequisites

Ensure Rust and Cargo are installed on your environment:

```bash
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y
source "$HOME/.cargo/env"

```

---

### 2. Create the Rust C-Dynamic Library

The crate lives in the **project root** — same place the C++ compressor sources
live. The CI workflow (`.github/workflows/build-oxc.yml`) builds it from the
root `Cargo.toml`, so there is no separate repo or subdirectory to manage.

Put this `Cargo.toml` at the project root:

```toml
[package]
name = "oxc_php_minifier"
version = "0.1.0"
edition = "2021"

[lib]
name = "oxc_php_minifier"
crate-type = ["cdylib"]

[dependencies]
# Pin to one real published Oxc release — the minifier API is pre-1.0 and its
# option shape changes between versions. Confirm these compile on the
# build_oxc branch before trusting the pin.
oxc_allocator = "0.153"
oxc_parser = "0.153"
oxc_minifier = "0.153"
oxc_codegen = "0.153"
oxc_span = "0.153"

# Release build tuned for a minifier called per request.
[profile.release]
opt-level = 3
lto = true
codegen-units = 1
```

Write the export functions in `src/lib.rs`.

**The scope argument mirrors the esbuild contract exactly** (`minifyJS.cpp`):

| scope      | what Oxc does                                          | matches esbuild                                      |
|------------|--------------------------------------------------------|------------------------------------------------------|
| `global`   | minify syntax + whitespace, **keep identifier names**  | `--minify-syntax --minify-whitespace --keep-names`   |
| `scoped`   | full minify + **mangle names**, wrap in an **IIFE**    | `--bundle --minify --format=iife`                    |

So a global script is compressed but its `function`/`class`/`var` names survive
(they're part of the page's public surface); a component script is squeezed all
the way and sealed in `(()=>{ … })();` so its locals can't leak.

**Error handling — no fallback.** A parse error is the developer's own broken JS,
so Oxc does not throw it away and does not fall back to the input. It returns the
error text (prefixed so the PHP side can tell it apart) and the caller renders it
as the JS body — a visible `console` error, page still serves. Only a genuine FFI
/ allocation failure returns a null pointer (that's the PHP side's hard throw).

```rust
use std::ffi::{CStr, CString};
use std::os::raw::c_char;

use oxc_allocator::Allocator;
use oxc_codegen::{Codegen, CodegenOptions};
use oxc_minifier::{CompressOptions, Minifier, MinifierOptions};
use oxc_parser::Parser;
use oxc_span::SourceType;

/// Sentinel the PHP wrapper checks for. When the dev's JS fails to parse we
/// return `__OXC_ERROR__<message>` as the result string instead of mangling
/// broken input or falling back to the original. The PHP side serves it as the
/// JS body verbatim, so the error shows up in the browser console.
const ERROR_PREFIX: &str = "__OXC_ERROR__";

fn error_string(msg: &str) -> *mut c_char {
    CString::new(format!("{ERROR_PREFIX}{msg}"))
        .map(|s| s.into_raw())
        .unwrap_or(std::ptr::null_mut())
}

/// Minify `source` under `scope` ("global" keeps names, "scoped" mangles + IIFE).
/// Returns a heap C-string that MUST be freed with `oxc_free_string`.
/// A null return means a hard FFI/allocation failure (PHP throws); a
/// `__OXC_ERROR__…` return means the input JS was invalid (PHP renders it).
#[no_mangle]
pub extern "C" fn oxc_minify(source: *const c_char, scope: *const c_char) -> *mut c_char {
    if source.is_null() {
        return std::ptr::null_mut();
    }

    let c_str = unsafe { CStr::from_ptr(source) };
    let Ok(source_text) = c_str.to_str() else {
        return error_string("source was not valid UTF-8");
    };

    // Default to global when no scope is supplied.
    let scope_str = if scope.is_null() {
        "global"
    } else {
        unsafe { CStr::from_ptr(scope) }.to_str().unwrap_or("global")
    };
    let scoped = scope_str.eq_ignore_ascii_case("scoped");

    let allocator = Allocator::default();
    // Plain browser script semantics, NOT a module — matches esbuild's
    // --platform=browser. Module mode (mjs) would force strict mode and change
    // top-level `this`, which is wrong for global/jQuery-style scripts.
    let source_type = SourceType::cjs();

    // 1. Parse. A diagnostic here is the developer's broken JS: surface it,
    //    do not fall back to the input.
    let ret = Parser::new(&allocator, source_text, source_type).parse();
    if !ret.diagnostics.is_empty() {
        let msg = ret
            .diagnostics
            .iter()
            .map(|d| d.to_string())
            .collect::<Vec<_>>()
            .join("; ");
        return error_string(&msg);
    }

    let mut program = ret.program;

    // 2. Compress. Mangle identifiers ONLY for scoped component scripts; a
    //    global script keeps its names (keep-names) so the page can reference
    //    them. CompressOptions still folds/strips in both cases.
    let options = MinifierOptions {
        mangle: if scoped { Some(Default::default()) } else { None },
        mangle_properties: None,
        compress: Some(CompressOptions::smallest()),
    };
    Minifier::new(options).minify(&allocator, &mut program);

    // 3. Print minified code.
    let codegen_options = CodegenOptions {
        minify: true,
        ..CodegenOptions::default()
    };
    let mut minified = Codegen::new()
        .with_options(codegen_options)
        .build(&program)
        .code;

    // 4. Scoped scripts are sealed in an IIFE so their locals never leak into
    //    the global namespace — the same guarantee esbuild's --format=iife gives.
    if scoped {
        minified = format!("(()=>{{{minified}}})();");
    }

    CString::new(minified)
        .map(|s| s.into_raw())
        .unwrap_or(std::ptr::null_mut())
}

#[no_mangle]
pub extern "C" fn oxc_free_string(s: *mut c_char) {
    if !s.is_null() {
        unsafe {
            drop(CString::from_raw(s));
        }
    }
}
```

---

### 3. Compile the Shared Object

Compile the library with release optimizations from the project root:

```bash
cargo build --release
```

The compiled library will be at:

* **Linux:** `target/release/liboxc_php_minifier.so`
* **macOS:** `target/release/liboxc_php_minifier.dylib`
* **Windows:** `target/release/oxc_php_minifier.dll`

In production you don't build this by hand — the `build-oxc.yml` workflow
compiles all three and attaches them to the GitHub release. Point
`useOxcForJsMinification()` (or `PHPSPA_OXC_LIB`) at wherever you place the
downloaded library.

---

### 4. Enable PHP FFI

Ensure FFI is active in your `php.ini`:

```ini
extension=ffi
ffi.enable=true

```

*(For CLI / dev scripts `ffi.enable=true` is sufficient. In persistent production servers like FrankenPHP/PHP-FPM, you can set `ffi.enable=preload` for preloading).*

---

### 5. PHP Implementation

The wrapper has exactly two failure modes, and they are deliberately different:

* **Infra / FFI failure** (lib can't load, null pointer back from Oxc) → **throw**.
  The dev opted in with `useOxcForJsMinification()`; a broken setup must fail
  loud, never silently serve weaker JS.
* **The dev's JS is invalid** → Oxc returns `__OXC_ERROR__<message>`. We **do not
  throw and do not fall back to the original source** — we hand the message back
  so the asset layer serves it as the JS body. The console shows the real syntax
  error; the page still loads.

```php
<?php

class OxcMinifier
{
    private static ?\FFI $ffi = null;

    /** Must match ERROR_PREFIX in src/lib.rs. */
    private const ERROR_PREFIX = '__OXC_ERROR__';

    public function __construct(string $libPath = __DIR__ . '/liboxc_php_minifier.so')
    {
        if (self::$ffi === null) {
            // FFI::cdef throws if the lib is missing/unloadable — that is the
            // hard "you enabled Oxc but didn't set it up" failure. Let it throw.
            self::$ffi = \FFI::cdef("
                char* oxc_minify(const char* source, const char* scope);
                void oxc_free_string(char* s);
            ", $libPath);
        }
    }

    /**
     * @param string $scope "global" (keep names) or "scoped" (mangle + IIFE).
     */
    public function minify(string $sourceCode, string $scope = 'global'): string
    {
        if (trim($sourceCode) === '') {
            return $sourceCode;
        }

        $cResult = self::$ffi->oxc_minify($sourceCode, $scope);

        // Null = genuine FFI/allocation failure. No fallback: throw loud.
        if (\FFI::isNull($cResult)) {
            throw new \RuntimeException(
                'Oxc minifier returned a null pointer (FFI/allocation failure). '
                . 'Check the Oxc library build.'
            );
        }

        $result = \FFI::string($cResult);
        self::$ffi->oxc_free_string($cResult); // free Rust heap immediately

        // A parse error is the developer's broken JS. Return it as-is (with the
        // sentinel stripped) so the asset layer serves it verbatim as the JS
        // body — a visible console error, NOT a fallback to the original source.
        if (str_starts_with($result, self::ERROR_PREFIX)) {
            $message = substr($result, strlen(self::ERROR_PREFIX));
            return "console.error(" . json_encode("[Oxc] " . $message) . ");";
        }

        return $result;
    }
}

// --- Test: global keeps names, scoped mangles + IIFE-wraps ---
$minifier = new OxcMinifier(__DIR__ . '/liboxc_php_minifier.so');

$jsCode = <<<JS
function markUserChatAsRead(username, updateServer = true) {
    const \$chatListItem = \$(`.user-chat[data-username='\${username}']`)
    if (!\$chatListItem.length) return

    const isOutgoing = \$chatListItem.attr('data-is-outgoing') === 'true'
    \$chatListItem.find('.lastMsg').css('font-weight', 'normal')

    if (!isOutgoing && !\$chatListItem.find('.notif .seen').hasClass('hidden')) {
        \$chatListItem.find('.notif').children().addClass('hidden')
    }

    if (!isOutgoing) {
        updateChatStatus(username, 'read')
    }

    if (!updateServer) return

    if (socket && socket.readyState == WebSocket.OPEN) {
        socket.send(JSON.stringify({
            "type": "mark_chat_as_read",
            "username": username
        }))
    }
}
JS;

echo "GLOBAL (names kept):\n";
echo $minifier->minify($jsCode, 'global'), "\n\n";

echo "SCOPED (mangled + IIFE):\n";
echo $minifier->minify($jsCode, 'scoped'), "\n";
```

Run it:

```bash
php Minifier.php
```

Global output keeps `markUserChatAsRead` and friends readable but strips all
whitespace; scoped output mangles every local and wraps the whole thing in
`(()=>{ … })();`. Semicolons are inserted by Oxc's own ASI handling, and the
whole thing runs in-process via the C-ABI — no child process, no temp files.