//! In-process JS minifier for phpspa, exposed over a C ABI for PHP FFI.
//!
//! Scope mirrors the esbuild contract in `src/compression/functions/minifyJS.cpp`:
//!   - scope "global"  -> top-level names are KEPT (the page/other scripts
//!                        reference them); only when the level mangles do the
//!                        INNER-scope locals get renamed.
//!   - scope "scoped"  -> component script, wrapped in an IIFE; everything
//!                        inside is fair game to mangle.
//!
//! Level maps phpspa's compression levels (the `nativeLevel` the PHP side sends):
//!   - 1 = BASIC      -> light output: codegen `minify: false` (whitespace only
//!                       trimmed) and COMMENTS KEPT. Mangle as first shipped
//!                       (global: none, scoped: default).
//!   - 2 = AGGRESSIVE -> codegen `minify: true` (strips whitespace) + comments
//!                       disabled via `CommentOptions::disabled()`. Same mangle
//!                       policy as BASIC.
//!   - 3 = EXTREME    -> `minify: true` + comments disabled + mangle as hard as
//!                       Oxc allows in BOTH scopes (global still keeps its
//!                       TOP-LEVEL names, but inner scopes are mangled; scoped
//!                       mangles everything) + the smallest CompressOptions.
//!
//! Two INDEPENDENT codegen levers (verified against oxc 0.153 docs + the
//! upstream `minifier.rs` example): the `minify` flag strips WHITESPACE only,
//! and `CommentOptions` controls COMMENTS. The `minify` flag does NOT remove
//! comments on its own — relying on it for that left comments in the output.
//!
//! The mangler writes renamed symbols into a `Scoping` table returned by
//! `Minifier::minify`; that table is passed to codegen with `.with_scoping(...)`
//! so the short names actually print. Dropping it (the earlier bug) ran the
//! mangler but discarded its result, printing the original names.
//!
//! No fallback: a parse error (the developer's broken JS) is returned as
//! `__OXC_ERROR__<message>` so the caller can serve it verbatim as the JS body;
//! only a genuine FFI/allocation failure returns a null pointer.

use std::ffi::{CStr, CString};
use std::os::raw::{c_char, c_int};

use oxc_allocator::Allocator;
use oxc_codegen::{Codegen, CodegenOptions, CommentOptions};
use oxc_minifier::{
	CompressOptions, MangleOptions, MangleOptionsKeepNames, Minifier, MinifierOptions,
};
use oxc_parser::Parser;
use oxc_span::SourceType;

/// Sentinel the PHP wrapper checks for. When the dev's JS fails to parse we
/// return `__OXC_ERROR__<message>` as the result string instead of mangling
/// broken input or falling back to the original.
const ERROR_PREFIX: &str = "__OXC_ERROR__";

// phpspa compression levels, as the PHP side maps them (HtmlCompressor::compressWithNative).
const LEVEL_BASIC: c_int = 1;
const LEVEL_AGGRESSIVE: c_int = 2;
const LEVEL_EXTREME: c_int = 3;

fn error_string(msg: &str) -> *mut c_char {
	CString::new(format!("{ERROR_PREFIX}{msg}"))
		.map(|s| s.into_raw())
		.unwrap_or(std::ptr::null_mut())
}

/// Build the mangler options for a given scope + level.
///
/// Returns `None` to mean "do not mangle at all" (the original BASIC global
/// behaviour), or `Some(..)` with the right top-level / keep-names policy.
fn mangle_options(scoped: bool, level: c_int) -> Option<MangleOptions> {
	match level {
		// BASIC / AGGRESSIVE: preserve the behaviour phpspa first shipped —
		// global is NOT mangled; scoped mangles with defaults. (AGGRESSIVE only
		// differs from BASIC by stripping comments, handled in codegen.)
		LEVEL_BASIC | LEVEL_AGGRESSIVE => {
			if scoped {
				Some(MangleOptions::default())
			} else {
				None
			}
		}
		// EXTREME: mangle as hard as possible in BOTH scopes.
		//   - scoped  -> mangle top-level too (it is sealed in an IIFE anyway).
		//   - global  -> KEEP top-level names (the page references them) but let
		//                every inner scope be renamed; reserve the UMD wrapper
		//                names so a prebuilt CommonJS/UMD module's exports survive.
		LEVEL_EXTREME => Some(MangleOptions {
			top_level: Some(scoped),
			keep_names: MangleOptionsKeepNames::all_false(),
			reserved: if scoped {
				Default::default()
			} else {
				["module"].into_iter().map(Into::into).collect()
			},
			debug: false,
		}),
		// Unknown level -> be conservative, same as BASIC.
		_ => {
			if scoped {
				Some(MangleOptions::default())
			} else {
				None
			}
		}
	}
}

/// Minify `source` under `scope` and `level`.
///
/// Returns a heap C-string that MUST be freed with `oxc_free_string`.
/// A null return means a hard FFI/allocation failure (PHP throws); a
/// `__OXC_ERROR__…` return means the input JS was invalid (PHP renders it).
///
/// # Safety
/// `source` and `scope` must be valid NUL-terminated C strings or null.
#[no_mangle]
pub extern "C" fn oxc_minify(
	source: *const c_char,
	scope: *const c_char,
	level: c_int,
) -> *mut c_char {
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

	// Human-readable level name for the log line below.
	let level_name = match level {
		LEVEL_BASIC => "BASIC",
		LEVEL_AGGRESSIVE => "AGGRESSIVE",
		LEVEL_EXTREME => "EXTREME",
		_ => "UNKNOWN",
	};

	// 2. Compress. Mangle policy is scope + level dependent (see mangle_options).
	//    CompressOptions::smallest() is the hardest Oxc offers and is used at
	//    every level — the per-level difference is mangling + whitespace/comments
	//    (the codegen `minify` flag below), matching phpspa's "basic keeps it
	//    light, aggressive strips whitespace+comments, extreme also mangles".
	let mangle = mangle_options(scoped, level);
	let mangled = mangle.is_some();
	let options = MinifierOptions {
		mangle,
		mangle_properties: None,
		compress: Some(CompressOptions::smallest()),
	};
	// CRITICAL: the Minifier computes the renamed-symbol table into `ret.scoping`.
	// That table MUST be handed to Codegen via `.with_scoping(...)` below, or the
	// mangled names are silently discarded and codegen prints the ORIGINAL names.
	// (This was the bug: the return value was dropped, so mangle ran but never
	// showed up in the output.)
	let ret = Minifier::new(options).minify(&allocator, &mut program);

	// 3. Print. Two independent, per-level levers — verified against the oxc
	//    0.153 codegen docs and the upstream `minifier.rs` example:
	//      - `minify` flag          -> strips WHITESPACE only.
	//      - `comments` option       -> strips COMMENTS (the `minify` flag does
	//                                    NOT remove comments; `CommentOptions`
	//                                    does). BASIC keeps comments; AGGRESSIVE
	//                                    and EXTREME disable them.
	//    Mapping:
	//      - BASIC      -> minify: false, comments kept        (light output)
	//      - AGGRESSIVE -> minify: true,  comments stripped
	//      - EXTREME    -> minify: true,  comments stripped + mangling applied
	let strip = level == LEVEL_AGGRESSIVE || level == LEVEL_EXTREME;
	let codegen_options = CodegenOptions {
		minify: strip,
		comments: if strip {
			CommentOptions::disabled()
		} else {
			CommentOptions::default()
		},
		..CodegenOptions::default()
	};
	let mut minified = Codegen::new()
		.with_options(codegen_options)
		// Apply the mangler's renamed-symbol table so the short names actually
		// appear in the printed source.
		.with_scoping(ret.scoping)
		.build(&program)
		.code;

	// Report to stderr what level/scope actually ran. PHP's FFI inherits this
	// process's stderr, so the line shows up in the PHP error log — the log the
	// caller asked the Rust side to emit, confirming the level is not stuck.
	// Suppressed when the PHP app called disableLogs() with the compression-log
	// bit, which exports PHPSPA_DISABLE_COMPRESSION_LOG into this process's env.
	if std::env::var_os("PHPSPA_DISABLE_COMPRESSION_LOG").is_none() {
		eprintln!(
			"[phpspa_oxc] minified scope={} level={} (minify={}, comments_stripped={}, mangle={})",
			if scoped { "scoped" } else { "global" },
			level_name,
			strip,
			strip,
			mangled,
		);
	}

	// 4. Scoped scripts are sealed in an IIFE so their locals never leak into
	//    the global namespace — the same guarantee esbuild's --format=iife gives.
	if scoped {
		minified = format!("(()=>{{{minified}}})();");
	}

	CString::new(minified)
		.map(|s| s.into_raw())
		.unwrap_or(std::ptr::null_mut())
}

/// Free a string returned by `oxc_minify`.
///
/// # Safety
/// `s` must be a pointer returned by `oxc_minify` and not already freed.
#[no_mangle]
pub extern "C" fn oxc_free_string(s: *mut c_char) {
	if !s.is_null() {
		unsafe {
			drop(CString::from_raw(s));
		}
	}
}
