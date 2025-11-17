#include <iostream>
#include <algorithm>
#include <cctype>
#include <vector>
#include "../HtmlCompressor.h"
#include "../../helper/explode.h"
#include "../../utils/trim.h"

void HtmlCompressor::removeWhitespace(std::string& html) {
   auto normalizeTag = [](const std::string& tag) -> std::string {
      std::string lowered = tag;

      std::transform(lowered.begin(), lowered.end(), lowered.begin(), [](unsigned char ch) -> char {
         return static_cast<char>(std::tolower(ch));
      });
      return lowered;
   };

   auto isSpecialTag = [](const std::string& tag) -> bool {
      return tag == "pre" || tag == "script" || tag == "style" || tag == "textarea" || tag == "code";
   };

   std::string result;
   result.reserve(html.length()); // Pre-allocate to avoid reallocations
   std::vector<std::string> tagStack;
   bool insideSpecial = false;
   bool pendingSpace = false;

   const size_t length = html.length();

   for (size_t i = 0; i < length; ++i) {
      char current = html[i];

      if (current == '<') {
         size_t tagEnd = html.find('>', i);
         if (tagEnd == std::string::npos) {
            break; // Malformed HTML, bail out
         }

         std::string tagContent = html.substr(i, tagEnd - i + 1);
         bool isClosingTag = (i + 1 < length && html[i + 1] == '/');
         bool isComment = (i + 3 < length && html[i + 1] == '!' && html[i + 2] == '-' && html[i + 3] == '-');

         if (isComment) {
            // Comments should already be removed upstream, but copy as-is for safety
            result += tagContent;
            i = tagEnd;
            pendingSpace = false;
            continue;
         }

         std::string tagName;
         bool selfClosing = false;

         if (isClosingTag) {
            size_t nameStart = i + 2;
            size_t nameEnd = html.find_first_of(" \n\t\r>", nameStart);

            if (nameEnd == std::string::npos || nameEnd > tagEnd) {
               nameEnd = tagEnd;
            }
            tagName = normalizeTag(html.substr(nameStart, nameEnd - nameStart));

            if (!tagStack.empty()) {
               // Pop stack until matching tag is found
               auto it = std::find(tagStack.rbegin(), tagStack.rend(), tagName);

               if (it != tagStack.rend()) {
                  size_t removeCount = std::distance(tagStack.rbegin(), it) + 1;
                  while (removeCount-- > 0 && !tagStack.empty()) {
                     tagStack.pop_back();
                  }
               }
            }

            insideSpecial = false;
            for (auto stackIt = tagStack.rbegin(); stackIt != tagStack.rend(); ++stackIt) {
               if (isSpecialTag(*stackIt)) {
                  insideSpecial = true;
                  break;
               }
            }
         } else {
            size_t nameStart = i + 1;
            size_t nameEnd = html.find_first_of(" \n\t\r/>", nameStart);
            if (nameEnd == std::string::npos || nameEnd > tagEnd) {
               nameEnd = tagEnd;
            }
            tagName = normalizeTag(html.substr(nameStart, nameEnd - nameStart));
            selfClosing = (tagEnd > i + 1 && html[tagEnd - 1] == '/');

            if (!selfClosing) {
               tagStack.push_back(tagName);
               if (isSpecialTag(tagName)) {
                  insideSpecial = true;
               }
            }
         }

         optimizeAttributes(tagContent);
         result += tagContent;

         pendingSpace = false;
         i = tagEnd;
         continue;
      }

      if (insideSpecial) {
         // Check if we're inside script or style tags to minify their content
         if (!tagStack.empty()) {
            std::string currentTag = tagStack.back();
            
            if (currentTag == "script" || currentTag == "style") {
               // Find the closing tag
               std::string closingTag = "</" + currentTag;
               size_t closingPos = html.find(closingTag, i);
               
               if (closingPos != std::string::npos) {
                  // Extract content between opening and closing tags
                  std::string content = html.substr(i, closingPos - i);
                  
                  // Minify based on tag type
                  if (currentTag == "script") {
                     minifyJS(content);
                  } else if (currentTag == "style") {
                     minifyCSS(content);
                  }
                  
                  result += content;
                  i = closingPos - 1; // Will be incremented by loop
                  continue;
               }
            }
         }
         
         result += current;
         continue;
      }

      if (isWhitespace(current)) {
         pendingSpace = true;
         continue;
      }

      if (pendingSpace && !result.empty() && result.back() != '>') {
         result += ' ';
      }

      result += current;
      pendingSpace = false;
   }

   html = result;
}