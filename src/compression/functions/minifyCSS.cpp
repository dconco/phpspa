#include "../HtmlCompressor.h"

std::string HtmlCompressor::minifyCSS(const std::string& css) {
   if (currentLevel < AGGRESSIVE) return css;

   std::string result;
   result.reserve(css.length()); // Pre-allocate to avoid reallocations
   bool inString = false;
   bool inUrl = false;
   char stringChar = '\0';
   size_t i = 0;

   while (i < css.length()) {
      char current = css[i];

      // Handle strings
      if (!inUrl && (current == '"' || current == '\'')) {
         if (!inString) {
            inString = true;
            stringChar = current;
         } else if (current == stringChar) {
            inString = false;
         }
         result += current;
         ++i;
         continue;
      }

      // Handle url()
      if (!inString && i + 3 < css.length() && css.substr(i, 4) == "url(") {
         inUrl = true;
         result += "url(";
         i += 4;
         continue;
      }
      if (inUrl && current == ')') {
         inUrl = false;
         result += ')';
         ++i;
         continue;
      }

      // Preserve content inside strings and urls
      if (inString || inUrl) {
         result += current;
         ++i;
         continue;
      }

      // Remove CSS comments
      if (current == '/' && i + 1 < css.length() && css[i + 1] == '*') {
         size_t endComment = css.find("*/", i + 2);
         if (endComment != std::string::npos) {
            i = endComment + 2;
            continue;
         }
      }

      // Remove whitespace
      if (std::isspace(static_cast<unsigned char>(current))) {
         ++i;
         continue;
      }

      result += current;
      ++i;
   }

   return result;
}
