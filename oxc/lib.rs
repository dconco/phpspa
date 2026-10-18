//! In-process JS minifier for phpspa, exposed over a C ABI for PHP FFI.
//!
//! Mirrors the esbuild contract in `src/compression/functions/minifyJS.cpp`:
//!   - scope "global"  -> minify syntax + whitespace, KEEP identifier names
//!                        (esbuild: --minify-syntax --minify-whitespace --keep-names)
//!   - scope "scoped"  -> full minify + MANGLE names, wrapped in an IIFE
//!                        (esbuild: --bundle --minify --format=iife)
//!
//! No fallback: a parse error (the developer's broken JS) is returned as
//! `__OXC_ERROR__<message>` so the caller can serve it verbatim as the JS body;
//! only a genuine FFI/allocation failure returns a null pointer.

use std::ffi::{CStr, CString};
use std::os::raw::c_char;

use oxc_allocator::Allocator;
use oxc_codegen::{Codegen, CodegenOptions};
use oxc_minifier::{CompressOptions, Minifier, MinifierOptions};
use oxc_parser::Parser;
use oxc_span::SourceType;

/// Sentinel the PHP wrapper checks for. When the dev's JS fails to parse we
/// return `__OXC_ERROR__<message>` as the result string instead of mangling
/// broken input or falling back to the original.
const ERROR_PREFIX: &str = "__OXC_ERROR__";

fn error_string(msg: &str) -> *mut c_char {
	CString::new(format!("{ERROR_PREFIX}{msg}"))
		.map(|s| s.into_raw())
		.unwrap_or(std::ptr::null_mut())
}

/// Minify `source` under `scope` ("global" keeps names, "scoped" mangles + IIFE).
///
/// Returns a heap C-string that MUST be freed with `oxc_free_string`.
/// A null return means a hard FFI/allocation failure (PHP throws); a
/// `__OXC_ERROR__…` return means the input JS was invalid (PHP renders it).
///
/// # Safety
/// `source` and `scope` must be valid NUL-terminated C strings or null.
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
