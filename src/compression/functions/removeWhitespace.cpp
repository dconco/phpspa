#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"
#include "../../helper/explode.h"

std::string HtmlCompressor::removeWhitespace(const std::string& html) {
   std::string result;
   std::string lastTag;
   std::string capturedContentToTrim;
   int tagDepth = 0;

   for (size_t i = 0; i < html.length(); i++) {
      char c = html.at(i);

      // --- MODE 1: We're inside a tag, capturing content ---
      if (!lastTag.empty()) {
         std::string closingTag = "</" + lastTag + ">";
         std::string openingTag = "<" + lastTag;

         // Check if this is a closing tag for our tracked tag
         if (html.compare(i, closingTag.length(), closingTag) == 0) {
            if (tagDepth == 0) {
               // This is OUR closing tag - process and exit capture mode
               // Check if THIS tag (lastTag) is a special tag
               bool isSpecial = (lastTag == "pre" || lastTag == "script" || 
                                lastTag == "style" || lastTag == "textarea");

               if (isSpecial) {
                  // Keep whitespace as-is
                  result += capturedContentToTrim + closingTag;
               } else {
                  // Normal processing - recurse without pre-trimming
                  result += removeWhitespace(capturedContentToTrim) + closingTag;
               }
               i += closingTag.length() - 1;
               lastTag.clear();
               capturedContentToTrim.clear();
               tagDepth = 0;
               continue;
            } else {
               // Nested same-tag closed, decrease depth
               tagDepth--;
               capturedContentToTrim += closingTag;
               i += closingTag.length() - 1;
               continue;
            }
         }

         // Check if this is an opening tag of same type (nested)
         if (html.compare(i, openingTag.length(), openingTag) == 0) {
            size_t nextCharPos = i + openingTag.length();
            if (nextCharPos < html.length() && 
                (html[nextCharPos] == '>' || html[nextCharPos] == ' ')) {
               // Found nested same tag
               tagDepth++;
            }
         }

         // Still capturing - add character to buffer
         capturedContentToTrim += c;
         continue;
      }

      // --- MODE 2: Not inside a tag, looking for opening tags ---
      if (c == '<' && i + 1 < html.length() && html[i + 1] != '/') {
         size_t endOpenTagPos = html.find('>', i);

         if (endOpenTagPos != std::string::npos) {
            std::string fullTag = html.substr(i + 1, endOpenTagPos - i - 1); // --- store tagname ---
            std::string tagNameOnly = explode(" ", fullTag).front(); // --- get only tag name without attributes ---

            // Start tracking this tag
            lastTag = tagNameOnly;
            result += "<" + fullTag + ">";
            i = endOpenTagPos;
            tagDepth = 0;
            continue;
         }
      }

      // Regular character outside tags
      result += c;
   }

   return result;
}