#include <iostream>
#include "../HtmlCompressor.h"
#include "../../utils/trim.h"
#include "../../helper/explode.h"

std::string HtmlCompressor::removeWhitespace(const std::string& html) {
   std::string result;
   std::string lastTag;
   std::string capturedContentToTrim;
   
   for (size_t i = 0; i < html.length(); i++) {

      if (!lastTag.empty()) {
         std::cout << "Last Tag: " << lastTag << std::endl;
         if (html.compare(i, lastTag.length() + 2, "</" + lastTag) == 0) {
            // --- Closing tag found, trim captured content ---
            capturedContentToTrim = trimWhitespace(capturedContentToTrim);
            std::cout << "Trimming content inside <" << lastTag << ">: '" << capturedContentToTrim << "'" << std::endl;
            // result += capturedContentToTrim;
            // result += "</" + lastTag + ">";
            // i += lastTag.length() + 3; // Move index to end of closing tag
            // lastTag.clear();
            // capturedContentToTrim.clear();
         }
      }

      if (html.at(i) == '<') {
         size_t endOpenTagPos = html.find('>', i);

         if (endOpenTagPos != std::string::npos) {
            std::string tag_name = html.substr(i + 1, endOpenTagPos + i - 1); // --- store tagname ---
            lastTag = explode(" ", tag_name).front(); // --- get only tag name without attributes ---
            result += "<" + tag_name + ">";
            i = endOpenTagPos; // Move index to end of tag
            continue;
         }
      }

      if (!lastTag.empty()) capturedContentToTrim += html.at(i);
      else result += html.at(i);
   }

   return result;
}