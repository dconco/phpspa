// =============================================================================
// Native minifyJS characterization tests
// (Task 3 of the windows-esbuild-timeout-fallback bugfix spec).
//
//   Property 2 - Preservation (non-buggy inputs unchanged).
//
// These tests pin down the CURRENT behavior of the native/internal minifier
// `HtmlCompressor::minifyJS(js, scope)` (the 2-arg overload). They establish
// the baseline that the cross-platform fix (design Changes 1-4) MUST preserve:
// the internal minifier's tokenization, comment stripping, semicolon
// insertion, spacing and `scoped` IIFE wrapping are explicitly out of scope for
// the fix and must not change.
//
//   EXPECTED OUTCOME: these tests PASS on the UNFIXED code (baseline behavior).
//
// There is no local C++ toolchain; the suite is validated by the CI
// `cpp-tests` job (Linux + Windows) wired into
// .github/workflows/build-compressor.yml.
//
// currentLevel is a SHARED STATIC. Every test sets it explicitly at the top so
// test ordering can never contaminate results.
//
//   _Requirements: 3.4 (non-congested behavior), 3.5 (internal minifier
//                  semantics preserved)_
// =============================================================================

#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

#include <string>

#include "compression/HtmlCompressor.h"

namespace {
   // Helper: run the native (2-arg) minifier and return the result by value so
   // assertions read cleanly.
   std::string minify(const std::string& source, const std::string& scope) {
      std::string js = source;
      HtmlCompressor::minifyJS(js, scope);
      return js;
   }
} // namespace

// -----------------------------------------------------------------------------
// Line comments are stripped (global scope, no IIFE wrapping).
//
// EXACT expected value derived by tracing the algorithm:
//   Input: "// leading\nvar a=1;"
//   - "//" enters single-line-comment mode; everything up to '\n' is dropped
//     and the newline sets pendingLinebreak.
//   - At '\n' boundary before "var": lastSignificant is '\0' (nothing emitted
//     yet), so no semicolon/space is injected.
//   - "var a=1;" is emitted; the single space in "var a" is an identifier /
//     identifier juxtaposition, so ONE space is preserved -> "var a".
//   => EXACT: "var a=1;"
// -----------------------------------------------------------------------------
TEST(minify_js_global_strips_line_comment_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minify("// leading\nvar a=1;", "global"), std::string("var a=1;"));
}

// -----------------------------------------------------------------------------
// Multi-line statements collapse with automatic semicolon insertion at the
// newline boundary, and a space is forced after an inserted ';' before an
// identifier.
//
// EXACT expected value derived by tracing:
//   Input: "a = 1\nb = 2\n"
//   - "a = 1"  -> "a=1"  (spaces around '=' dropped: '=' is not an identifier
//                         body char, so needsSpaceBetween is false)
//   - newline boundary before "b": lastSignificant '1' is a statement-end char
//     and 'b' is a statement-start char -> insert ';'. Because the next token
//     starts an identifier, forceSpaceBeforeNextToken is set, so a single space
//     is emitted before 'b'.
//   - "b = 2" -> "b=2"; the trailing "\n" is pending at end-of-input and never
//     flushed.
//   => EXACT: "a=1; b=2"
// -----------------------------------------------------------------------------
TEST(minify_js_global_semicolon_insertion_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minify("a = 1\nb = 2\n", "global"), std::string("a=1; b=2"));
}

// -----------------------------------------------------------------------------
// Identifier-adjacency spacing is preserved: "return x" must keep exactly one
// space (dropping it would corrupt the code into "returnx").
//
// EXACT expected value derived by tracing:
//   Input: "return   x"
//   - "return" emitted; the run of spaces collapses to pendingSpace; at 'x'
//     needsSpaceBetween('n','x') is true (both identifier-body chars) and the
//     output does not already end in a space, so ONE space is emitted.
//   => EXACT: "return x"
// -----------------------------------------------------------------------------
TEST(minify_js_global_preserves_identifier_adjacency_space_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minify("return   x", "global"), std::string("return x"));
}

// -----------------------------------------------------------------------------
// String / template-literal contents are preserved verbatim, including internal
// spaces, across the three quote styles ('...', "...", `...`).
//
// Exact whitespace outside the literals is easy to over-predict, so this test
// asserts the robust INVARIANT that each literal's exact bytes survive intact.
// -----------------------------------------------------------------------------
TEST(minify_js_preserves_string_literal_contents) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string single = minify("var s = 'hello   world';", "global");
   CHECK(single.find("'hello   world'") != std::string::npos);

   const std::string dbl = minify("var s = \"a  b  c\";", "global");
   CHECK(dbl.find("\"a  b  c\"") != std::string::npos);

   const std::string tmpl = minify("var s = `x   y`;", "global");
   CHECK(tmpl.find("`x   y`") != std::string::npos);
}

// -----------------------------------------------------------------------------
// scoped scope wraps the (trimmed) body in an IIFE `(()=>{ ... ;})();`, trimming
// trailing ';' and whitespace from the body before wrapping.
//
// EXACT expected value derived by tracing:
//   Input: "const x=1;\n"
//   - native body minifies to "const x=1;" (the trailing "\n" is a dangling
//     pendingLinebreak that is never flushed).
//   - scoped wrapping trims trailing ';'/whitespace -> "const x=1", then wraps:
//     "(()=>{" + "const x=1" + ";})();".
//   => EXACT: "(()=>{const x=1;})();"
// -----------------------------------------------------------------------------
TEST(minify_js_scoped_iife_wrapping_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minify("const x=1;\n", "scoped"),
            std::string("(()=>{const x=1;})();"));
}

// -----------------------------------------------------------------------------
// scoped wrapping INVARIANTS for a richer body: output starts with the IIFE
// opener and ends with the IIFE closer, regardless of the exact minified body.
// These are prefix/suffix invariants (robust to internal spacing).
// -----------------------------------------------------------------------------
TEST(minify_js_scoped_iife_prefix_suffix_invariants) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string out = minify("const x = 1;\nconsole.log(x);\n", "scoped");
   CHECK(!out.empty());
   CHECK(out.rfind("(()=>{", 0) == 0);           // starts with the IIFE opener
   const std::string suffix = "})();";
   CHECK(out.size() >= suffix.size());
   CHECK(out.compare(out.size() - suffix.size(), suffix.size(), suffix) == 0);
}

// -----------------------------------------------------------------------------
// EXTREME strips /* block */ comments; AGGRESSIVE does NOT (block-comment
// handling is gated on currentLevel == EXTREME).
//
//   AGGRESSIVE: the "/* keep */" markers survive (INVARIANT: "/*" and "*/" are
//               still present in the output).
//   EXTREME:    the block comment is removed (INVARIANT: no "/*" remains); the
//               surrounding statement "var a=1;" survives.
// Substring invariants are used because the exact surrounding whitespace is not
// the point being characterized here.
// -----------------------------------------------------------------------------
TEST(minify_js_block_comment_gated_on_extreme_vs_aggressive) {
   const std::string source = "/* keep */var a=1;";

   // AGGRESSIVE: block comment is NOT stripped.
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   const std::string aggressive = minify(source, "global");
   CHECK(aggressive.find("/*") != std::string::npos);
   CHECK(aggressive.find("*/") != std::string::npos);
   CHECK(aggressive.find("var a=1;") != std::string::npos);

   // EXTREME: block comment IS stripped, statement survives.
   HtmlCompressor::currentLevel = HtmlCompressor::EXTREME;
   const std::string extreme = minify(source, "global");
   CHECK(extreme.find("/*") == std::string::npos);
   CHECK(extreme.find("var a=1;") != std::string::npos);
}

// -----------------------------------------------------------------------------
// Property-style coverage (Property 2 - Preservation): across a small corpus and
// both scopes, the native minifier preserves string-literal contents and
// produces balanced IIFE wrapping for scoped / no wrapping for global.
//
// **Validates: Requirements 3.5**
// -----------------------------------------------------------------------------
TEST(minify_js_property_scope_wrapping_and_literal_preservation) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   struct Sample {
      std::string js;
      std::string literal; // a literal whose exact bytes must survive
   };
   const Sample samples[] = {
      {"var a = 'keep me';",            "'keep me'"},
      {"let b = \"two  spaces\";",      "\"two  spaces\""},
      {"const c = `tmpl  lit`;",        "`tmpl  lit`"},
      {"function f(){ return 'z  z'; }", "'z  z'"},
   };

   for (const auto& s : samples) {
      // global: never IIFE-wrapped, literal preserved verbatim.
      const std::string g = minify(s.js, "global");
      CHECK(g.find(s.literal) != std::string::npos);
      CHECK(g.rfind("(()=>{", 0) != 0); // does NOT start an IIFE

      // scoped: IIFE-wrapped (balanced opener/closer), literal preserved.
      const std::string sc = minify(s.js, "scoped");
      CHECK(sc.rfind("(()=>{", 0) == 0);
      const std::string suffix = "})();";
      CHECK(sc.size() >= suffix.size());
      CHECK(sc.compare(sc.size() - suffix.size(), suffix.size(), suffix) == 0);
      CHECK(sc.find(s.literal) != std::string::npos);
   }
}
