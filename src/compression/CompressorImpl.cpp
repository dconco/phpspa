#include "HtmlCompressor.h"
#include "static/currentLevel.hh"

std::string HtmlCompressor::compress(const std::string& html, const Level level) {
   std::string compressedHtml = html;
   HtmlCompressor::currentLevel = level;

   compressedHtml.reserve(html.size());

   if (level >= BASIC) removeWhitespace(compressedHtml);
   if (level >= AGGRESSIVE) removeComments(compressedHtml);
   // if (level >= EXTREME) optimizeAttributes(compressedHtml);

   return compressedHtml;
}
