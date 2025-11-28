#include "HtmlCompressor.h"

// --- Define static member variable ---
HtmlCompressor::Level HtmlCompressor::currentLevel{ HtmlCompressor::BASIC };

std::string HtmlCompressor::compress(const std::string& html) {
   std::string compressedHtml = html;

   compressedHtml.reserve(html.size());

   if (HtmlCompressor::currentLevel >= BASIC) minifyHTML(compressedHtml);
   if (HtmlCompressor::currentLevel >= AGGRESSIVE) removeComments(compressedHtml);
   // if (level >= EXTREME) optimizeAttributes(compressedHtml); // --- This is done in the minifyHTML function ---

   return compressedHtml;
}
