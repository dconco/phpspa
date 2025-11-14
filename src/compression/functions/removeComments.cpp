#include <iostream>
#include "../HtmlCompressor.h"

std::string HtmlCompressor::removeComments(const std::string& html) {
   std::string result;
   size_t position = 0;

   for (position = 0; position < html.length();) {
      if (html.compare(position, 4, "<!--") == 0) {
         size_t endComment = html.find("-->", position + 4);

         if (endComment != std::string::npos) {
            position = endComment + 3; // Move past the end of the comment
         } else {
            break; // No closing tag found, exit loop
         }
      } else {
         result += html[position];
         position++;
      }
   }

   return result;
}