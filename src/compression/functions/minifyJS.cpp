#include <cctype>
#include "../HtmlCompressor.h"

std::string HtmlCompressor::minifyJS(const std::string& js) {
   if (currentLevel < AGGRESSIVE) return js;

   std::string result;
   result.reserve(js.length()); // Pre-allocate to avoid reallocations
   bool inString = false;
   bool inRegex = false;
   bool inSingleComment = false;
   bool inMultiComment = false;
   char stringChar = '\0';
   char prev = '\0';
   size_t i = 0;

   while (i < js.length()) {
      char current = js[i];
      char next = (i + 1 < js.length()) ? js[i + 1] : '\0';

      if (currentLevel == EXTREME) {
         // Handle multi-line comments
         if (!inString && !inRegex && !inSingleComment && current == '/' && next == '*') {
            inMultiComment = true;
            i += 2;
            continue;
         }
         if (inMultiComment) {
            if (current == '*' && next == '/') {
               inMultiComment = false;
               i += 2;
               prev = ' ';
               continue;
            }
            ++i;
            continue;
         }
      }

      // Handle single-line comments
      if (!inString && !inRegex && !inMultiComment && current == '/' && next == '/') {
         inSingleComment = true;
         i += 2;
         continue;
      }
      if (inSingleComment) {
         if (current == '\n' || current == '\r') {
            inSingleComment = false;
         }
         ++i;
         continue;
      }

      // Handle strings
      if (!inRegex && (current == '"' || current == '\'' || current == '`')) {
         if (!inString) {
            inString = true;
            stringChar = current;
         } else if (current == stringChar && prev != '\\') {
            inString = false;
         }
         result += current;
         prev = current;
         ++i;
         continue;
      }

      // Preserve string content
      if (inString) {
         result += current;
         prev = current;
         ++i;
         continue;
      }

      // Remove whitespace (but preserve necessary spacing)
      if (std::isspace(static_cast<unsigned char>(current))) {
         // Add space only if needed between alphanumeric characters
         if (!result.empty() && std::isalnum(static_cast<unsigned char>(prev)) && std::isalnum(static_cast<unsigned char>(next))) {
            if (result.back() != ' ') {
               result += ' ';
            }
         }
         prev = ' ';
         ++i;
         continue;
      }

      result += current;
      prev = current;
      ++i;
   }

   return result;
}
