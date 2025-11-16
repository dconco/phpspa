#include <iostream>
#include "../HtmlCompressor.h"

std::string HtmlCompressor::removeComments(const std::string& html) {
   std::string result;
   size_t lastPos = 0;
   size_t pos = 0;

   while ((pos = html.find("<!--", lastPos)) != std::string::npos) {
      result += html.substr(lastPos, pos - lastPos);  // Copy content before comment
      size_t endPos = html.find("-->", pos + 4);
      
      if (endPos != std::string::npos) {
         lastPos = endPos + 3;  // Move past the comment
      } else {
         lastPos = html.length();  // No closing tag, skip to end
         break;
      }
   }

   result += html.substr(lastPos);  // Copy remaining content after last comment
   return result;
}