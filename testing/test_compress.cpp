// =============================================================================
// compress() pipeline + level-gating characterization tests
// (Task 3 of the windows-esbuild-timeout-fallback bugfix spec).
//
//   Property 2 - Preservation (non-buggy inputs unchanged).
//
// Drives behavior through the PUBLIC `HtmlCompressor::compress(html)` entry
// point (the private minifyHTML / removeComments are exercised THROUGH it, per
// the design's "prefer the public seam" guidance). Characterizes:
//   - BASIC:      minifyHTML runs (inter-tag whitespace collapses) but
//                 removeComments does NOT (an HTML comment survives).
//   - AGGRESSIVE: removeComments also runs (the comment is gone).
//   - special-tag handling: <pre> whitespace preserved; inline <style> and
//                 inline <script> content minified.
// The cross-platform fix (design Changes 1-4) touches only minifyJS.cpp; this
// pipeline behavior must remain unchanged.
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
   bool contains(const std::string& hay, const std::string& needle) {
      return hay.find(needle) != std::string::npos;
   }
} // namespace

// -----------------------------------------------------------------------------
// BASIC: minifyHTML runs (compress calls it at >= BASIC) so inter-tag whitespace
// collapses, but removeComments does NOT run (gated at >= AGGRESSIVE) so an HTML
// comment survives.
//
// Derived by tracing compress(): at BASIC only minifyHTML executes. minifyHTML
// collapses runs of whitespace between tags to nothing when the previous
// emitted char is '>' (a pending space is only emitted when the last char is
// not '>'). Comment spans are copied verbatim by minifyHTML.
//
//   INVARIANT: the comment "<!-- keep -->" is still present, AND the inter-tag
//   double space has been collapsed (no "  " run remains).
// -----------------------------------------------------------------------------
TEST(compress_basic_collapses_whitespace_but_keeps_comments) {
   HtmlCompressor::currentLevel = HtmlCompressor::BASIC;

   const std::string out =
      HtmlCompressor::compress("<div>  <span>hi</span>  </div><!-- keep -->");

   // removeComments did NOT run at BASIC: the comment survives.
   CHECK(contains(out, "<!-- keep -->"));
   // minifyHTML DID run: the double-space runs between tags are gone.
   CHECK(!contains(out, "  "));
   // Structure/content is intact.
   CHECK(contains(out, "<div>"));
   CHECK(contains(out, "<span>hi</span>"));
}

// -----------------------------------------------------------------------------
// AGGRESSIVE: removeComments ALSO runs (compress calls it at >= AGGRESSIVE), so
// the HTML comment is stripped while the surrounding markup remains.
//
//   INVARIANT: "<!-- keep -->" is gone; the div/span content survives.
// -----------------------------------------------------------------------------
TEST(compress_aggressive_removes_comments) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string out =
      HtmlCompressor::compress("<div>  <span>hi</span>  </div><!-- gone -->");

   // removeComments ran: the comment is stripped.
   CHECK(!contains(out, "<!-- gone -->"));
   CHECK(!contains(out, "gone"));
   // Markup content survives.
   CHECK(contains(out, "<div>"));
   CHECK(contains(out, "<span>hi</span>"));
}

// -----------------------------------------------------------------------------
// <pre> content whitespace is preserved (pre is a "special" tag; its inner text
// is copied character-by-character, unlike ordinary inter-tag whitespace).
//
//   INVARIANT: the multi-space run inside <pre> survives verbatim.
// Derived by tracing minifyHTML: while inside a special tag that is NOT
// script/style, each character (including spaces/newlines) is written verbatim.
// -----------------------------------------------------------------------------
TEST(compress_preserves_pre_whitespace) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string out =
      HtmlCompressor::compress("<pre>a   b\n  c</pre>");

   CHECK(contains(out, "<pre>"));
   CHECK(contains(out, "</pre>"));
   // The interior whitespace run is preserved (would be collapsed outside <pre>).
   CHECK(contains(out, "a   b\n  c"));
}

// -----------------------------------------------------------------------------
// Inline <style> content is minified via minifyCSS (special-tag handling routes
// style content through minifyCSS).
//
//   INVARIANT: the CSS body is tightened to "body{color:red}" (minifyCSS runs at
//   AGGRESSIVE), and the <style> wrapper survives.
// Derived by tracing: inside a <style> special tag, the content up to </style is
// passed to minifyCSS, whose AGGRESSIVE pipeline yields "body{color:red}" for
// "body { color : red ; }" (see test_minify_css.cpp).
// -----------------------------------------------------------------------------
TEST(compress_minifies_inline_style) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string out =
      HtmlCompressor::compress("<style>body { color : red ; }</style>");

   CHECK(contains(out, "<style>"));
   CHECK(contains(out, "</style>"));
   CHECK(contains(out, "body{color:red}"));
}

// -----------------------------------------------------------------------------
// Inline <script> content is minified via the native minifyJS (special-tag
// handling routes script content through minifyJS with the default "global"
// scope, i.e. no IIFE wrapping).
//
//   INVARIANT: the leading line comment is stripped from the script body while
//   the statement survives; the <script> is not IIFE-wrapped (global scope).
// Derived by tracing: inside a <script> special tag, content up to </script is
// passed to minifyJS(content) (2-arg, default scope "global"), which strips the
// "// c" comment and keeps "var a=1;" (see test_minify_js.cpp).
// -----------------------------------------------------------------------------
TEST(compress_minifies_inline_script) {
   HtmlCompressor::currentLevel = HtmlCompressor::AGGRESSIVE;

   const std::string out =
      HtmlCompressor::compress("<script>// c\nvar a=1;</script>");

   CHECK(contains(out, "<script>"));
   CHECK(contains(out, "</script>"));
   // Line comment stripped by the native minifier.
   CHECK(!contains(out, "// c"));
   // Statement survives; global scope means NO IIFE wrapping.
   CHECK(contains(out, "var a=1;"));
   CHECK(!contains(out, "(()=>{"));
}
