#include "HtmlCompressor.h"
#include "static/currentLevel.hh"

std::string HtmlCompressor::compress(const std::string& html, const Level level) {
   std::string compressedHtml = html;
   HtmlCompressor::currentLevel = level;

   if (level >= BASIC) compressedHtml = removeWhitespace(compressedHtml);
   if (level >= AGGRESSIVE) compressedHtml = removeComments(compressedHtml);
   // if (level >= EXTREME) compressedHtml = optimizeAttributes(compressedHtml);

   return compressedHtml;
}
