// =============================================================================
// minifyCSS characterization tests
// (Task 3 of the windows-esbuild-timeout-fallback bugfix spec).
//
//   Property 2 - Preservation (non-buggy inputs unchanged).
//
// Pins down the CURRENT behavior of the PUBLIC `HtmlCompressor::minifyCSS(css)`:
//   - below AGGRESSIVE it is a no-op (level gate), and
//   - at/above AGGRESSIVE it strips comments, collapses whitespace, trims spaces
//     around { } ; : , removes a trailing ';' before '}', normalizes zero units
//     and leading-zero decimals, and shortens rgb() to hex.
// The cross-platform fix (design Changes 1-4) touches only minifyJS.cpp; this
// CSS behavior must remain unchanged, so these assertions establish the
// baseline.
//
//   EXPECTED OUTCOME: these tests PASS on the UNFIXED code (baseline behavior).
//
// currentLevel is a SHARED STATIC. Every test sets it explicitly at the top.
//
//   _Requirements: 3.1 (BASIC internal-only / level gating), 3.4 (non-congested
//                  behavior), 3.5 (internal minifier semantics preserved)_
// =============================================================================

#define PHPSPA_TEST_MAIN
#include "test_framework.hpp"

#include <string>

#include "compression/HtmlCompressor.h"

namespace {
   std::string minifyCss(const std::string& source) {
      std::string css = source;
      HtmlCompressor::minifyCSS(css);
      return css;
   }
} // namespace

// -----------------------------------------------------------------------------
// BASIC: minifyCSS is a no-op because `currentLevel < AGGRESSIVE` returns early.
// The input must be returned byte-for-byte unchanged.
//
//   EXACT: output == input.
// -----------------------------------------------------------------------------
TEST(minify_css_basic_is_noop) {
   HtmlCompressor::currentLevel = HtmlCompressor::BASIC;

   const std::string input = "a {\n   color : red ;\n}\n/* keep me */";
   CHECK_EQ(minifyCss(input), input);
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: canonical rule minification.
//
// EXACT expected value derived by tracing the pipeline for "a { color : red ; }":
//   collapseWhitespace   -> "a { color : red ; }"
//   stripSpaceAround('{') -> "a{color : red ; }"
//   stripSpaceAround('}') -> "a{color : red ;}"
//   stripSpaceAround(';') -> "a{color : red;}"
//   stripSpaceAround(':') -> "a{color:red;}"
//   stripSemicolonBeforeBrace -> "a{color:red}"
//   => EXACT: "a{color:red}"
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_rule_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minifyCss("a { color : red ; }"), std::string("a{color:red}"));
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: comments are stripped and surrounding whitespace collapsed.
//
// EXACT expected value derived by tracing for "a { /* c */ color:red }":
//   stripComments        -> "a {  color:red }"   (the /* c */ span is removed)
//   collapseWhitespace   -> "a { color:red }"
//   stripSpaceAround('{') -> "a{color:red }"
//   stripSpaceAround('}') -> "a{color:red}"
//   (':' already tight; no trailing ';')
//   => EXACT: "a{color:red}"
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_strips_comments_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minifyCss("a { /* c */ color:red }"), std::string("a{color:red}"));
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: a url() value is protected (placeholder) and restored verbatim.
//
// EXACT expected value derived by tracing for "a { background : url(foo.png) }":
//   url(foo.png) is captured as a placeholder BEFORE minification and restored
//   verbatim afterward, so its bytes are untouched. The rest minifies as usual:
//   => EXACT: "a{background:url(foo.png)}"
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_preserves_url_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minifyCss("a { background : url(foo.png) }"),
            std::string("a{background:url(foo.png)}"));
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: a quoted string value is protected and restored verbatim,
// including internal spaces.
//
// INVARIANT: the exact quoted literal survives (the surrounding tightening is
// covered by other tests). Derived by tracing: quoted strings are captured as
// placeholders before minification and restored unchanged.
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_preserves_quoted_string) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   const std::string out =
      minifyCss("a { content : \"hi   there\" ; }");
   CHECK(out.find("\"hi   there\"") != std::string::npos);
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: a zero with a length unit is normalized to bare "0".
//
// EXACT expected value derived by tracing for "a { margin : 0px ; }":
//   after the space/semicolon tightening -> "a{margin:0px}", then the zero-unit
//   regex \b0+(px|...) -> "0":
//   => EXACT: "a{margin:0}"
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_zero_unit_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minifyCss("a { margin : 0px ; }"), std::string("a{margin:0}"));
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: rgb(255,255,255) is shortened to the 3-digit hex "#fff".
//
// EXACT expected value derived by tracing for "a { color : rgb(255,255,255) ; }":
//   tightening -> "a{color:rgb(255,255,255)}" (spaces inside rgb() are collapsed
//   by the earlier whitespace pass; the rgb regex tolerates optional spaces),
//   then 255->"ff" for each channel; all channel hex pairs are equal so the
//   value shortens to "#fff":
//   => EXACT: "a{color:#fff}"
// -----------------------------------------------------------------------------
TEST(minify_css_aggressive_rgb_to_hex_exact) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;
   CHECK_EQ(minifyCss("a { color : rgb(255,255,255) ; }"),
            std::string("a{color:#fff}"));
}

// -----------------------------------------------------------------------------
// EXTREME behaves like AGGRESSIVE for CSS (the gate is `< AGGRESSIVE`, so
// EXTREME >= AGGRESSIVE runs the full pipeline). Same exact result as the
// AGGRESSIVE canonical case.
// -----------------------------------------------------------------------------
TEST(minify_css_extreme_matches_aggressive_pipeline) {
   HtmlCompressor::currentLevel = HtmlCompressor::EXTREME;
   CHECK_EQ(minifyCss("a { color : red ; }"), std::string("a{color:red}"));
}
