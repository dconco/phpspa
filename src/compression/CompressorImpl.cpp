#include "HtmlCompressor.h"

std::string HtmlCompressor::compress(const std::string& html, const Level level) {
   std::string compressedHtml = html;

   if (level >= BASIC) compressedHtml = removeWhitespace(compressedHtml);
   if (level >= AGGRESSIVE) compressedHtml = removeComments(compressedHtml);
   if (level >= EXTREME) compressedHtml = optimizeAttributes(compressedHtml);

   return compressedHtml;
}
