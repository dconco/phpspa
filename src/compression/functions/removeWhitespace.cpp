#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"
#include "../../helper/explode.h"

std::string HtmlCompressor::removeWhitespace(const std::string& html) {
   std::string result;
   std::string lastTag;
   std::string capturedContentToTrim;
   
   for (size_t i = 0; i < html.length(); i++) {
      char c = html.at(i);

      if (!lastTag.empty()) {
         std::string closingTag = "</" + lastTag + ">";

         if (html.compare(i, closingTag.length(), closingTag) == 0) {
            // --- Closing tag found, trim captured content ---
            result += trimWhitespace(capturedContentToTrim) + closingTag;
            i += closingTag.length() - 1; // Move index to end of closing tag

            lastTag.clear();
            capturedContentToTrim.clear();
            continue;
         } else {
            capturedContentToTrim += c;
            continue;
         }
      }

      if (c == '<' && i + 1 < html.length() && html[i + 1] != '/') {
         size_t endOpenTagPos = html.find('>', i);

         if (endOpenTagPos != std::string::npos) {
            std::string fullTag = html.substr(i, endOpenTagPos - i + 1);

            // Extract tag name (before space or >)
            size_t tagNameEnd = fullTag.find_first_of(" >", 1);
            lastTag = fullTag.substr(1, tagNameEnd - 1);

            result += fullTag;
            i = endOpenTagPos;
            continue;
         }
      }

      result += c;
   }

   std::cout << "Final result: '" << result << "'" << std::endl;
   return result;
}